import { DuelTransport } from './transport';
import { dialog, closeDialog } from './dialog';
import { connectGame } from './adapters';
import { animateAnswerFeedback } from './answerFeedback';
import { DuelPresence } from './presence';
import { DuelIdle } from './idle';
import { renderDuelResults } from './results';

export class DuelClient {
    constructor(state, createGame) {
        this.state = state;
        this.createGame = createGame;
        this.transport = new DuelTransport();
        this.game = null;
        this.queue = Promise.resolve();
        this.connected = false;
        this.starting = false;
        this.localProgress = this.you().progress;
        this.sequence = state.sequence;
        this.finishPending = false;
        this.leaving = false;
        this.rematching = false;
        this.gameNeedsRestart = false;
        this.answerIds = new Set();
        this.hud = document.getElementById('duel-hud');
        this.results = this.hud.querySelector('[data-duel-results]');
    }
    you() { return this.state.players.find(player => player.role === this.state.role); }
    opponent() { return this.state.players.find(player => player.role !== this.state.role); }

    begin() {
        document.body.classList.add('duel-mode');
        this.hud.hidden = false;
        document.querySelector('#progress-bar')?.closest('.mb-2')?.classList.add('d-none');
        $('#page-wrapper').show();
        $('#page-wrapper').attr('inert', '');
        this.render();
        document.addEventListener('click', event => {
            if (event.target.closest('[data-duel-leave]')) this.leave();
            if (event.target.closest('[data-duel-rematch]')) this.rematch();
        });
        if (['cancelled', 'expired'].includes(this.state.status)) {
            this.receive(this.state);
            return;
        }
        if (this.state.status !== 'finished') dialog({ phase: 'connecting', message: 'Connecting to your Duel…', leave: true });
        this.idle = new DuelIdle({ onExpire: () => this.leave({ automatic: true }), onResume: () => {
            this.receive(this.state);
            if (this.state.status === 'countdown' || (!this.game && this.state.status === 'playing')) this.tick();
            else if (this.state.status === 'playing') closeDialog();
        } }).start();
        this.presence = new DuelPresence(this.transport, this.state.id, state => this.receive(state), error => this.error(error));
        if (this.state.status !== 'finished') this.presence.start();
        else this.idle.stop();
        this.transport.subscribe(this.state.id, {
            update: state => this.receive(state),
            answer: event => this.receiveAnswer(event),
            connected: async () => {
                this.connected = true;
                try { this.receive(await this.transport.request(`/${this.state.id}`)); }
                catch (error) { this.error(error); }
            },
            disconnected: () => { this.connected = false; this.render(); },
            error: () => this.error(new Error('Could not connect to multiplayer. Please try again.')),
        });
        document.querySelector('[data-duel-ready]').addEventListener('click', async event => {
            const button = event.currentTarget;
            button.disabled = true;
            // Ready is also an audio-unlock gesture for browser autoplay policies.
            try { await window.Tone?.start?.(); } catch (_) {}
            try { this.receive(await this.transport.request(`/${this.state.id}/ready`, { seed: this.state.seed })); }
            catch (error) { button.disabled = false; this.error(error); }
        });
        this.tickTimer = setInterval(() => this.tick(), 100);
        window.addEventListener('online', () => this.transport.request(`/${this.state.id}`).then(state => this.receive(state)).catch(error => this.error(error)));
    }

    receive(state) {
        if (state.id !== this.state.id || state.revision < this.state.revision) return;
        const newMatch = state.seed && state.seed !== this.state.seed;
        this.state = { ...this.state, ...state };
        if (newMatch) {
            this.localProgress = this.you().progress;
            this.sequence = this.state.sequence || 0;
            this.finishPending = false;
            this.queue = Promise.resolve();
            this.answerIds.clear();
            this.clearAnswerFeedback?.();
            this.gameNeedsRestart = !!this.game;
            this.results.hidden = true;
            delete this.results.dataset.outcome;
            document.body.classList.remove('duel-results-open');
            this.hud.hidden = false;
            this.idle?.stop();
            this.idle?.start();
            this.presence?.stop();
            this.presence = new DuelPresence(this.transport, this.state.id, next => this.receive(next), error => this.error(error));
            this.presence.start();
        }
        this.render();
        if (['cancelled', 'expired'].includes(this.state.status)) {
            this.idle?.stop();
            this.clearAnswerFeedback?.();
            this.game?._stopLoop?.();
            this.game?._stopGameTimer?.();
            this.game?._cancelTimers?.();
            this.game?._cancelCardAudition?.();
            this.presence?.stop();
            this.results.hidden = true;
            document.body.classList.remove('duel-results-open');
            $('#page-wrapper').attr('inert', '');
            const message = this.state.left_by ? (this.state.left_by === this.state.role ? 'You left the Duel.' : 'Opponent left the Duel.') : `This Duel is ${this.state.status}.`;
            dialog({ phase: this.state.left_by ? (this.state.left_by === this.state.role ? 'you-left' : 'opponent-left') : this.state.status, message, exit: true });
            return;
        }
        if (this.state.status === 'finished') this.idle?.stop();
        if (this.idle?.warning) return;
        if (this.state.status === 'ready') {
            dialog({ phase: this.you().ready ? 'waiting-ready' : 'ready', opponentReady: this.opponent()?.ready,
                message: this.you().ready ? '✓ You are ready · Waiting for opponent…' : 'Opponent connected ✓', ready: !this.you().ready, leave: true });
        }
        if (this.you().finished_at) closeDialog();
        if (this.state.status === 'finished') {
            this.presence?.stop();
            closeDialog(); this.showResults();
        }
    }

    async tick() {
        this.renderConnection();
        if (this.idle?.warning) return;
        if (!['countdown', 'playing'].includes(this.state.status)) return;
        const remaining = Date.parse(this.state.starts_at) - (Date.now() + this.transport.offset);
        if (remaining > 0) {
            dialog({ phase: 'countdown', message: String(Math.min(3, Math.ceil(remaining / 1000))), leave: true });
            return;
        }
        if ((this.game && !this.gameNeedsRestart) || this.starting || this.leaving || this.you().finished_at) return;
        this.starting = true;
        try {
            // A browser clock can never authorize the start. Confirm it with Laravel.
            const state = await this.transport.request(`/${this.state.id}`);
            this.receive(state);
            if (this.state.status !== 'playing' || this.leaving || this.idle?.warning) return;
            dialog({ phase: 'go', message: 'GO!' });
            setTimeout(() => { if (this.state.status === 'playing' && !this.idle?.warning) closeDialog(); }, 350);
            $('#page-wrapper').removeAttr('inert');
            window.__activeDuel = this;
            if (this.gameNeedsRestart) {
                this.gameNeedsRestart = false;
                this.game._restartDuel();
                return;
            }
            if (this.you().progress === this.state.total) {
                this.game = connectGame(this.createGame(this.state.options), this, { finishOnly: true });
                return;
            }
            this.game = connectGame(this.createGame(this.state.options), this);
        } catch (error) { this.error(error); }
        finally { this.starting = false; }
    }

    enqueue(action, data) {
        data = { ...data, seed: this.state.seed };
        const work = async () => {
            for (let attempt = 0; ; attempt++) {
                if (data.seed !== this.state.seed || this.leaving || ['cancelled', 'expired', 'finished'].includes(this.state.status)) return;
                try {
                    const state = await this.transport.request(`/${this.state.id}/${action}`, data);
                    this.receive(state);
                    $('[data-duel-error]').text('').prop('hidden', true).hide();
                    return;
                } catch (error) {
                    if (data.seed !== this.state.seed || this.leaving || ['cancelled', 'expired'].includes(this.state.status)) return;
                    this.error(error);
                    if (error.status && error.status < 500 && error.status !== 429) throw error;
                    await new Promise(resolve => setTimeout(resolve, Math.min(10000, 500 * 2 ** attempt)));
                }
            }
        };
        this.queue = this.queue.then(work);
        // Keep the queue rejected on invalid transitions so finishing cannot bypass progress.
        this.queue.catch(() => {
            if (data.seed !== this.state.seed || this.leaving || ['cancelled', 'expired'].includes(this.state.status)) return;
            $('#page-wrapper').attr('inert', '');
            dialog({ phase: 'error', message: 'Your game needs to reconnect. Refresh to restore the last saved round.', leave: true, exit: true });
        });
        return this.queue;
    }

    answer(correct) {
        if (this.leaving || this.state.status !== 'playing') return;
        // Cosmetic feedback must never delay saved progress or prevent finishing.
        this.transport.request(`/${this.state.id}/answer`, { correct }).catch(() => {});
    }

    receiveAnswer(event) {
        if (this.leaving || !['playing', 'finished'].includes(this.state.status) || event.duel_id !== this.state.id ||
            event.role !== this.opponent()?.role || typeof event.correct !== 'boolean' || this.answerIds.has(event.id)) return;
        this.answerIds.add(event.id);
        if (this.answerIds.size > 100) this.answerIds.delete(this.answerIds.values().next().value);
        this.clearAnswerFeedback = animateAnswerFeedback(this.hud.querySelector('[data-duel-row="opponent"] [data-duel-name]'), event.correct);
    }

    progress(current, score, checkpoint) {
        if (current <= this.localProgress || this.finishPending || this.leaving || this.state.status !== 'playing') return;
        this.localProgress = current;
        this.enqueue('progress', { sequence: ++this.sequence, progress: current, score, checkpoint });
    }
    finished(result) {
        if (this.finishPending || this.you().finished_at || this.leaving || this.state.status !== 'playing') return;
        this.finishPending = true;
        $('#page-wrapper').attr('inert', '');
        this.enqueue('finish', { score: Math.round(result.score), accuracy: Math.round(result.accuracy) }).then(() => {
            if (this.you().finished_at && !['cancelled', 'expired'].includes(this.state.status)) this.showResults();
        }).catch(() => {});
    }

    async leave({ automatic = false } = {}) {
        if (this.leaving) return;
        this.leaving = true;
        this.idle?.stop();
        this.clearAnswerFeedback?.();
        $('#page-wrapper').attr('inert', '');
        const buttons = document.querySelectorAll('[data-duel-leave]');
        buttons.forEach(button => { button.disabled = true; });
        if (automatic) {
            this.presence?.stop();
            this.transport.leaveOnExit(this.state.id);
            this.transport.echo?.disconnect();
            window.location.href = window.__duelConfig.home;
            return;
        }
        try {
            // Bypass queued gameplay updates so a failed connection cannot trap the player.
            this.receive(await this.transport.request(`/${this.state.id}/leave`, {}));
            this.presence?.stop();
            this.transport.echo?.disconnect();
            window.location.href = window.__duelConfig.home;
        } catch (error) {
            this.leaving = false;
            this.idle?.start();
            buttons.forEach(button => { button.disabled = false; });
            this.error(error);
            dialog({ phase: 'error', message: 'Could not leave the Duel. Try again or return to all games.', leave: true, exit: true, error: error.message });
        }
    }

    async rematch() {
        if (this.rematching || this.leaving || this.state.status !== 'finished' || this.you().rematch) return;
        this.rematching = true;
        const button = this.results.querySelector('[data-duel-rematch]');
        const errorMessage = this.results.querySelector('[data-duel-rematch-error]');
        button.disabled = true;
        errorMessage.hidden = true;
        try {
            try { await window.Tone?.start?.(); } catch (_) {}
            this.receive(await this.transport.request(`/${this.state.id}/rematch`, { seed: this.state.seed }));
        } catch (error) {
            errorMessage.textContent = error.message;
            errorMessage.hidden = false;
        } finally {
            this.rematching = false;
            button.disabled = !!this.you().rematch;
        }
    }

    render() {
        for (const [row, player] of [['you', this.you()], ['opponent', this.opponent()]]) {
            if (!player) continue;
            const root = this.hud.querySelector(`[data-duel-row="${row}"]`);
            const percent = Math.min(100, player.progress * 100 / this.state.total);
            root.querySelector('[data-duel-bar]').style.width = `${percent}%`;
            root.querySelector('[data-duel-bar]').setAttribute('aria-valuenow', percent);
            root.querySelector('[data-duel-count]').textContent = `${player.progress} / ${this.state.total}`;
            root.querySelector('[data-duel-score]').textContent = `ϟ ${player.score}`;
        }
        const status = this.hud.querySelector('[data-duel-status]');
        status.textContent = ['cancelled', 'expired'].includes(this.state.status) ? 'Duel ended' : this.state.status === 'finished' ? 'Duel finished' :
            this.state.status === 'ready' ? 'Getting ready' : this.state.status === 'countdown' ? 'Starting…' : 'Match in progress';
        status.classList.toggle('is-playing', this.state.status === 'playing');
        status.classList.toggle('text-green', this.state.status === 'playing');
        if (this.you().finished_at) this.showResults();
    }
    renderConnection() {
        const connection = this.hud.querySelector('[data-duel-connection]');
        if (['cancelled', 'expired'].includes(this.state.status)) {
            connection.textContent = this.state.left_by && this.state.left_by !== this.state.role ? 'Opponent left the Duel' : 'Duel ended';
            connection.hidden = false;
            return;
        }
        const opponent = this.opponent();
        const disconnected = !this.connected || opponent?.disconnected || (opponent && Date.now() + this.transport.offset - Date.parse(opponent.last_seen_at) > 45000);
        connection.textContent = !this.connected ? 'Reconnecting…' :
            opponent?.finished_at ? 'Opponent finished ✓' : disconnected ? 'Opponent disconnected…' : '';
        connection.hidden = !connection.textContent;
    }
    showResults() {
        if (['cancelled', 'expired'].includes(this.state.status)) return;
        $('#page-wrapper').attr('inert', '');
        renderDuelResults(this.results, this.state);
        this.hud.hidden = true;
    }
    error(error) {
        $('[data-duel-error]').text(error.message).prop('hidden', false).show();
        const connection = this.hud.querySelector('[data-duel-connection]');
        connection.textContent = error.message;
        connection.hidden = false;
    }
}

export function bootGame(createGame) {
    if (!window.__duelState) { const game = createGame(); game.start?.(); return game; }
    const client = new DuelClient(window.__duelState, createGame);
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => client.begin(), { once: true });
    else client.begin();
    return client;
}
