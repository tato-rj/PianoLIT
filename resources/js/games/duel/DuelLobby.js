import { DuelTransport } from './transport';
import { dialog, closeDialog } from './dialog';
import { DuelPresence } from './presence';
import { DuelIdle } from './idle';
import { bindDuelCodeInputs } from './codeInputs';

const ROOM_KEY = 'pianolit.games.duel.waiting';
function saveRoom(id) { try { sessionStorage.setItem(ROOM_KEY, id); } catch (_) {} }
function forgetRoom() { try { sessionStorage.removeItem(ROOM_KEY); } catch (_) {} }
function savedRoom() { try { return sessionStorage.getItem(ROOM_KEY); } catch (_) { return null; } }

export function serializeSettings(form) {
    const settings = {};
    for (const input of form.elements) {
        const name = input.name?.replace(/\[\]$/, '');
        if (!name || input.type === 'submit' || input.type === 'button') continue;
        if (input.name.endsWith('[]')) {
            settings[name] ||= [];
            if (input.checked) settings[name].push(input.value);
        } else if (input.type === 'checkbox') settings[name] = input.checked;
        else if (input.type === 'radio') { if (input.checked) settings[name] = input.value; }
        else settings[name] = input.type === 'number' || input.type === 'range' ? Number(input.value) : input.value;
    }
    return settings;
}

$(function () {
    if (!window.__duelConfig || window.__duelState) return;
    const transport = new DuelTransport();
    const joinForm = document.querySelector('[data-duel-join-form]');
    const codeInputs = bindDuelCodeInputs(joinForm);
    let room = null;
    let pending = false;
    let navigating = false;
    let expiryTimer = null;
    let presence = null;
    let idle = null;

    const showError = error => {
        $('[data-duel-error]').text(error.message).prop('hidden', false).show();
    };
    const receive = state => {
        if (!room || state.id !== room.id || state.revision < room.revision) return;
        room = { ...room, ...state };
        if (room.status === 'waiting_for_opponent') return;
        if (['expired', 'cancelled'].includes(room.status)) {
            idle?.stop();
            presence?.stop();
            forgetRoom(); clearTimeout(expiryTimer);
            dialog({ phase: room.left_by ? 'opponent-left' : room.status, message: `This Duel has ${room.status === 'expired' ? 'expired' : 'been cancelled'}.`, exit: true });
            return;
        }
        if (navigating) return;
        navigating = true;
        idle?.stop();
        presence?.stop();
        forgetRoom(); clearTimeout(expiryTimer);
        dialog({ phase: 'joined', message: 'Opponent joined ✓ Opening your game…' });
        setTimeout(() => { location.href = room.game_url; }, 500);
    };
    const waitForOpponent = state => {
        room = state;
        saveRoom(room.id);
        dialog({ message: 'Your duel code is · Waiting for opponent…', code: room.code, cancel: true });
        idle?.stop();
        idle = new DuelIdle({ onExpire: () => {
            navigating = true;
            presence?.stop();
            clearTimeout(expiryTimer); forgetRoom();
            transport.leaveOnExit(room.id);
            transport.echo?.disconnect();
            location.href = window.__duelConfig.home;
        }, onResume: () => dialog({ message: 'Your duel code is · Waiting for opponent…', code: room.code, cancel: true }) }).start();
        presence?.stop();
        presence = new DuelPresence(transport, room.id, state => {
            // The transport refreshes authoritative state independently of presence heartbeats.
            if (['expired', 'cancelled'].includes(state.status)) receive(state);
        }, showError);
        presence.start();
        // Subscribe after the initial response so joins cannot be missed.
        transport.subscribe(room.id, {
            update: receive,
            connected: () => transport.request(`/${room.id}`).then(receive).catch(showError),
            disconnected: () => showError(new Error('Reconnecting to the Duel server…')),
            error: () => showError(new Error('Could not connect to multiplayer. Please try again.')),
        });
        expiryTimer = setTimeout(() => transport.request(`/${room.id}`).then(receive).catch(showError),
            Math.max(0, Date.parse(room.expires_at) - Date.now() - transport.offset) + 50);
    };

    document.addEventListener('click', async event => {
        const create = event.target.closest('[data-duel-create]');
        const join = event.target.closest('[data-duel-join]');
        if (join) {
            $('#duel-modal').one('shown.bs.modal', () => codeInputs.focus());
            dialog({ join: true });
        }
        if (!create || pending) return;
        pending = true; create.disabled = true;
        try {
            const form = create.closest('form');
            const state = await transport.request('', { game: create.dataset.duelCreate, settings: serializeSettings(form) });
            $(form.closest('.modal')).modal('hide');
            waitForOpponent(state);
        } catch (error) { dialog({ join: false, error: error.message, exit: true }); }
        finally { pending = false; create.disabled = false; }
    });
    joinForm.addEventListener('submit', async event => {
        event.preventDefault();
        const code = codeInputs.value();
        if (pending || !/^\d{4}$/.test(code)) return;
        pending = true;
        const button = event.target.querySelector('button'); button.disabled = true;
        try {
            const state = await transport.request('/join', { code });
            forgetRoom(); location.href = state.game_url;
        } catch (error) { showError(error); }
        finally { pending = false; button.disabled = false; }
    });
    document.querySelector('[data-duel-cancel]').addEventListener('click', async () => {
        if (pending || !room) return;
        pending = true;
        try { receive(await transport.request(`/${room.id}/cancel`, {})); forgetRoom(); closeDialog(); transport.echo?.disconnect(); }
        catch (error) { showError(error); }
        finally { pending = false; }
    });
    const previous = savedRoom();
    if (previous) transport.request(`/${previous}`).then(state => {
        if (state.status === 'waiting_for_opponent') waitForOpponent(state);
        else { room = state; receive(state); }
    }).catch(forgetRoom);
});
