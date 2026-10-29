import Offcanvas from 'bootstrap/js/dist/offcanvas';
import Dropdown from 'bootstrap/js/dist/dropdown';
import { MetronomeClock } from './metronome/clock';
import { clampTempo, DEFAULT_TEMPO, TempoGesture } from './metronome/gesture';
import { colorPalette, DEFAULT_SETTINGS, loadSettings, loadTempo, normalizeSettings, SETTINGS_KEY, TEMPO_KEY } from './metronome/settings';
import { conductorPreparationBeats, ConductorView } from './metronome/conductor';
import { VoiceRecorder } from './metronome/voice-recorder';
import { SoundMenu } from './metronome/sound-menu';
import { VoiceEffectSelection, processVoiceInWorker } from './metronome/voice-effect-selection';
import effects from '../data/metronome-voice-effects.json';
import { SettingsPages } from './metronome/settings-pages';
import { SpokenCounting, SpokenVoiceLibrary, fetchSpokenAudio } from './metronome/spoken-voices';
import { BottomSheetDrag } from './controls/bottom-sheet-drag';
import { FontSelect } from './controls/font-select';
import initializeLogoTilt from './components/logo-tilt';

initializeLogoTilt(window);

const surface = document.querySelector('[data-metronome]');
const pulseLayer = document.querySelector('[data-metronome-pulse]');
const number = document.querySelector('[data-tempo]');
const message = document.querySelector('[data-metronome-message]');
const edgeButtons = document.querySelectorAll('[data-tempo-step]');
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
const settingsButton = document.querySelector('[data-settings-open]');
const settingsPanel = document.querySelector('[data-settings-panel]');
const settingsOffcanvas = new Offcanvas(settingsPanel);
if (settingsPanel.querySelector('[data-sheet-drag-handle]')) {
    new BottomSheetDrag(settingsPanel, () => settingsOffcanvas.hide());
}
const hubMenu = document.querySelector('#public-hub-menu');
const beatInputs = document.querySelectorAll('[name="metronome-beats"]');
const typeInputs = document.querySelectorAll('[name="metronome-type"]');
const markerInputs = document.querySelectorAll('[name="metronome-marker"]');
const conductorOptions = document.querySelector('[data-conductor-options]');
const colorInput = document.querySelector('[data-settings-color]');
const colorValue = document.querySelector('[data-color-value]');
const colorReset = document.querySelector('[data-settings-color-reset]');
const soundInput = document.querySelector('#metronome-sound');
const fontInput = document.querySelector('#metronome-font');
const fontMenuRoot = document.querySelector('[data-font-select]');
const fontMenu = fontMenuRoot ? new FontSelect(fontMenuRoot, Dropdown) : null;
const pathInput = document.querySelector('[name="metronome-path"]');
const conductor = new ConductorView(document.querySelector('[data-conductor]'), { pulseLayer, number });

let tempo = DEFAULT_TEMPO;
let tempoSaveTimer = null;
let gesture = null;
let pointerId = null;
let suppressClick = false;
let pulse = null;
let backgroundPulse = null;
let backgroundPulseTempo = DEFAULT_TEMPO;
let edgePress = null;
let edgeRepeatTimer = null;
let settingsOpen = false;
let navigationOpen = false;
let settings = { ...DEFAULT_SETTINGS };
let voiceRecorder = null;
let soundMenu = null;
let settingsPages = null;
let spokenCounting = null;
let spokenId = null;
let voiceEffects = null;
let restoreSpoken = null;
try {
    settings = loadSettings(window.localStorage);
    tempo = loadTempo(window.localStorage);
} catch (error) {
    // Private browsing can make storage unavailable; all controls still work.
}
let palette = colorPalette(settings.color);

const clock = new MetronomeClock({
    createContext: () => {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) throw new Error('Web Audio is unavailable.');
        return new AudioContext({ latencyHint: 'interactive' });
    },
    onStateChange: running => {
        surface.setAttribute('aria-pressed', String(running));
        updateLabel();
        if (!running && pulse) pulse.cancel();
        if (!running && backgroundPulse) backgroundPulse.cancel();
        syncConductor();
    },
    onBeat: beat => {
        if (settings.type === 'conductor') {
            return;
        }
        if (reducedMotion.matches) return;
        if (beat === 0) {
            if (backgroundPulse) backgroundPulse.cancel();
            backgroundPulseTempo = tempo;
            backgroundPulse = pulseLayer.animate([
                { opacity: 1 },
                { opacity: 0 },
            ], { duration: 60000 / tempo, easing: 'ease-out' });
        }
        if (pulse) pulse.cancel();
        pulse = number.animate([
            { transform: 'scale(1.35)', opacity: 1 },
            { transform: 'scale(1)', opacity: 0.88 },
        ], { duration: 180, easing: 'ease-out' });
    },
    onReady: () => syncConductor(true),
    onTick: () => { if (settings.type === 'conductor') syncConductor(); },
    onError: () => {
        message.textContent = 'Sound couldn’t start. Tap to try again.';
        message.hidden = false;
    },
});

function updateLabel() {
    surface.setAttribute('aria-label', `${clock.running ? 'Stop' : 'Start'} metronome. ${tempo} beats per minute.`);
    edgeButtons.forEach(button => {
        const action = Number(button.dataset.tempoStep) > 0 ? 'Increase' : 'Decrease';
        button.setAttribute('aria-label', `${action} tempo by 1. ${tempo} beats per minute.`);
    });
}

function setTempo(value) {
    const previousTempo = tempo;
    tempo = Math.round(clampTempo(value));
    number.textContent = String(tempo);
    fontMenu?.render();
    if (backgroundPulse && backgroundPulse.playState === 'running') {
        // Keep the current fade in step with tempo changes during a swipe.
        backgroundPulse.updatePlaybackRate(tempo / backgroundPulseTempo);
    }
    clock.setTempo(tempo);
    if (tempo !== previousTempo) syncConductor(true);
    updateLabel();
    if (tempo !== previousTempo) {
        clearTimeout(tempoSaveTimer);
        // Only save a tempo that has settled; settings edits and playback
        // controls must not save an intermediate swipe value early.
        tempoSaveTimer = setTimeout(() => {
            tempoSaveTimer = null;
            try {
                window.localStorage.setItem(TEMPO_KEY, JSON.stringify(tempo));
            } catch (error) {
                // Tempo controls remain usable when storage is unavailable.
            }
        }, 5000);
    }
}

function toggle() {
    if (settingsOpen || navigationOpen) return;
    message.hidden = true;
    restoreSpoken?.();
    if (clock.running) clock.stop();
    else clock.start({ leadInBeats: settings.type === 'conductor' ? conductorPreparationBeats(settings.beats) : 0 });
}

function syncConductor(force = false) {
    if (settings.type !== 'conductor') {
        conductor.stop();
        return;
    }
    const position = clock.getBeatPosition();
    if (reducedMotion.matches) position.progress = 0;
    const animate = clock.ready && !position.waiting && !settingsOpen && !navigationOpen && !document.hidden && !reducedMotion.matches;
    // After a scheduler stall, align to the next real click, not an imagined
    // beat beyond the last sound that was actually scheduled.
    if (animate && position.progress === 1 && !force) return;
    conductor.sync(position, position.tempo, animate, force);
}

function applySettings(value, persist = true) {
    const previous = settings;
    settings = normalizeSettings(value);
    if (settings.sound === 'my-voice' && !clock.voiceBuffers) settings.sound = 'click';
    if (settings.sound.startsWith('spoken:') && settings.sound !== `spoken:${spokenId}`) settings.sound = 'click';
    palette = colorPalette(settings.color);
    if (previous.type !== settings.type) {
        if (pulse) pulse.cancel();
        if (backgroundPulse) backgroundPulse.cancel();
    }
    if (previous.color !== settings.color && backgroundPulse) backgroundPulse.cancel();
    document.documentElement.style.setProperty('--metronome-color', settings.color);
    document.documentElement.style.setProperty('--metronome-pulse', palette.pulse);
    document.documentElement.style.setProperty('--metronome-ink', palette.ink);
    document.documentElement.style.setProperty('--site-header-ink', palette.headerInk);
    document.querySelector('meta[name="theme-color"]').setAttribute('content', settings.color);
    surface.dataset.mode = settings.type;
    surface.dataset.showPath = String(settings.showPath);
    surface.dataset.conductorMarker = settings.conductorMarker;
    number.dataset.numberFont = settings.font;
    fontInput.value = settings.font;
    fontMenu?.render();
    clock.setBeats(settings.beats);
    clock.setSound(settings.sound.startsWith('spoken:') ? 'spoken' : settings.sound);
    if (conductor.beats !== settings.beats) conductor.setBeats(settings.beats);
    beatInputs.forEach(input => { input.checked = Number(input.value) === settings.beats; });
    typeInputs.forEach(input => { input.checked = input.value === settings.type; });
    markerInputs.forEach(input => { input.checked = input.value === settings.conductorMarker; });
    conductorOptions.hidden = settings.type !== 'conductor';
    colorInput.value = settings.color;
    colorValue.textContent = settings.color.toUpperCase();
    if (voiceRecorder) voiceRecorder.syncSound(settings.sound);
    else soundInput.value = settings.sound;
    soundMenu?.render(voiceRecorder);
    syncVoiceEffects();
    pathInput.checked = settings.showPath;
    settingsPages?.summary('sound', soundInput.selectedOptions?.[0]?.text || 'Click');
    settingsPages?.summary('rhythm', `${settings.beats} ${settings.beats === 1 ? 'beat' : 'beats'} · ${settings.type === 'conductor' ? 'Conductor' : 'Pulse'}`);
    settingsPages?.summary('appearance', fontInput.selectedOptions?.[0]?.text || 'Color & numbers');
    syncConductor();
    if (persist) {
        try {
            // The voice choice belongs to this account; guests and another
            // account on the same browser retain an ordinary instrument sound.
            window.localStorage.setItem(SETTINGS_KEY, JSON.stringify({ ...settings, sound: settings.sound === 'my-voice' ? 'click' : settings.sound }));
        } catch (error) {
            // Settings remain usable for the current visit.
        }
    }
}

settingsButton.addEventListener('click', () => {
    if (!settingsOpen) settingsOffcanvas.show(settingsButton);
});
settingsPanel.addEventListener('show.bs.offcanvas', () => {
    settingsOpen = true;
    restoreSpoken?.();
    stopEdgeRepeat();
    gesture = null;
    pointerId = null;
    surface.classList.remove('is-dragging');
    if (voiceRecorder && !voiceRecorder.ready) voiceRecorder.load();
    settingsButton.setAttribute('aria-expanded', 'true');
    syncConductor();
});
settingsPanel.addEventListener('hide.bs.offcanvas', () => {
    spokenCounting?.cancel();
    applySettings(settings, false);
    soundMenu?.close();
    voiceRecorder?.cancel();
});
settingsPanel.addEventListener('hidden.bs.offcanvas', () => {
    settingsOpen = false;
    settingsButton.setAttribute('aria-expanded', 'false');
    settingsButton.focus();
    syncConductor();
});
hubMenu?.addEventListener('show.bs.offcanvas', () => {
    navigationOpen = true;
    stopEdgeRepeat();
    gesture = null;
    pointerId = null;
    surface.classList.remove('is-dragging');
    syncConductor();
});
hubMenu?.addEventListener('hidden.bs.offcanvas', () => {
    navigationOpen = false;
    syncConductor();
});
beatInputs.forEach(input => input.addEventListener('change', () => {
    if (input.checked) applySettings({ ...settings, beats: Number(input.value) });
}));
typeInputs.forEach(input => input.addEventListener('change', () => {
    if (input.checked) applySettings({ ...settings, type: input.value });
}));
markerInputs.forEach(input => input.addEventListener('change', () => {
    if (input.checked) applySettings({ ...settings, conductorMarker: input.value });
}));
colorInput.addEventListener('input', () => applySettings({ ...settings, color: colorInput.value }));
fontInput.addEventListener('change', () => applySettings({ ...settings, font: fontInput.value }));
colorReset.addEventListener('click', () => applySettings({ ...settings, color: DEFAULT_SETTINGS.color }));
soundInput.addEventListener('change', () => {
    restoreSpoken = null;
    spokenCounting?.cancel();
    document.querySelector('[data-spoken-feedback]')?.setAttribute('hidden', '');
    if (soundInput.value.startsWith('spoken:')) {
        voiceRecorder?.remember('spoken');
        spokenCounting?.select(soundInput.value.slice(7));
        return;
    }
    if (voiceRecorder?.selectSound(soundInput.value)) return;
    applySettings({ ...settings, sound: soundInput.value });
    voiceRecorder?.remember(settings.sound);
});
pathInput.addEventListener('change', () => applySettings({ ...settings, showPath: pathInput.checked }));
reducedMotion.addEventListener('change', () => {
    if (reducedMotion.matches) {
        if (pulse) pulse.cancel();
        if (backgroundPulse) backgroundPulse.cancel();
    }
    syncConductor();
});

surface.addEventListener('pointerdown', event => {
    if (settingsOpen || navigationOpen || !event.isPrimary || event.button !== 0 || pointerId !== null || edgePress) return;
    pointerId = event.pointerId;
    suppressClick = false;
    gesture = new TempoGesture(tempo, event.clientX, event.clientY, event.timeStamp);
    surface.setPointerCapture(pointerId);
});

function movePointer(event) {
    if (event.pointerId !== pointerId || !gesture) return;
    setTempo(gesture.move(event.clientX, event.clientY, event.timeStamp));
    suppressClick = gesture.moved;
    surface.classList.toggle('is-dragging', gesture.moved);
}

surface.addEventListener('pointermove', movePointer);
surface.addEventListener('pointerup', event => {
    if (event.pointerId !== pointerId) return;
    movePointer(event);
    gesture = null;
    pointerId = null;
    surface.classList.remove('is-dragging');
});

function cancelGesture(event) {
    if (event.pointerId !== pointerId) return;
    suppressClick = true;
    gesture = null;
    pointerId = null;
    surface.classList.remove('is-dragging');
}

surface.addEventListener('pointercancel', cancelGesture);
surface.addEventListener('lostpointercapture', cancelGesture);
surface.addEventListener('click', event => {
    // Keyboard and assistive-technology clicks have no pointer click count.
    if (event.detail > 0 && suppressClick) {
        event.preventDefault();
        return;
    }
    toggle();
});

function stopEdgeRepeat() {
    clearTimeout(edgeRepeatTimer);
    edgeRepeatTimer = null;
    edgePress = null;
}

function repeatEdgeStep() {
    if (!edgePress) return;
    setTempo(tempo + edgePress.step);
    edgeRepeatTimer = setTimeout(repeatEdgeStep, 75);
}

edgeButtons.forEach(button => {
    const step = Number(button.dataset.tempoStep);
    button.addEventListener('pointerdown', event => {
        if (settingsOpen || navigationOpen || !event.isPrimary || event.button !== 0 || pointerId !== null || edgePress) return;
        edgePress = { button, pointerId: event.pointerId, step };
        button.setPointerCapture(event.pointerId);
        setTempo(tempo + step);
        edgeRepeatTimer = setTimeout(repeatEdgeStep, 350);
    });

    const release = event => {
        if (edgePress && edgePress.button === button && edgePress.pointerId === event.pointerId) {
            stopEdgeRepeat();
        }
    };
    button.addEventListener('pointerup', release);
    button.addEventListener('pointercancel', release);
    button.addEventListener('lostpointercapture', release);
    button.addEventListener('contextmenu', event => event.preventDefault());
    button.addEventListener('click', event => {
        // Pointer presses already step immediately; keep native keyboard/AT clicks.
        if (!settingsOpen && !navigationOpen && event.detail === 0) setTempo(tempo + step);
    });
});

document.addEventListener('keydown', event => {
    if (settingsOpen || navigationOpen || event.altKey || event.ctrlKey || event.metaKey) return;
    if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
        event.preventDefault();
        setTempo(tempo + (event.key === 'ArrowUp' ? 1 : -1) * (event.shiftKey ? 10 : 1));
    } else if (event.code === 'Space' || event.key === 'Enter') {
        if (event.target?.closest('a[href], [data-tempo-step], [data-settings-open], button:not([data-metronome])')) return;
        event.preventDefault();
        if (!event.repeat) toggle();
    } else if (event.key === 'Escape') {
        stopEdgeRepeat();
        clock.stop();
    }
});

window.addEventListener('blur', stopEdgeRepeat);
document.addEventListener('visibilitychange', () => {
    if (document.hidden) spokenCounting?.cancel();
    if (document.hidden) soundMenu?.close();
    if (document.hidden) voiceRecorder?.cancel();
    if (document.hidden) stopEdgeRepeat();
    syncConductor();
});
window.addEventListener('pagehide', () => {
    voiceEffects?.dispose();
    spokenCounting?.cancel();
    soundMenu?.close();
    voiceRecorder?.cancel();
    stopEdgeRepeat();
    clock.releaseAudio();
});
const preferredSpoken = settings.sound.startsWith('spoken:') ? settings.sound.slice(7) : null;
applySettings(settings, false);
setTempo(tempo);
const soundMenuRoot = document.querySelector('[data-sound-menu]');
if (soundMenuRoot) {
    const voices = JSON.parse(soundMenuRoot.dataset.spokenVoices || '[]');
    let decoder;
    const library = new SpokenVoiceLibrary({ voices, fetchAudio: fetchSpokenAudio, decode: bytes => {
        const OfflineContext = window.OfflineAudioContext || window.webkitOfflineAudioContext;
        if (!OfflineContext) return clock.getContext().decodeAudioData(bytes);
        if (!decoder) decoder = new OfflineContext(1, 1, 24000);
        return decoder.decodeAudioData(bytes);
    } });
    spokenCounting = new SpokenCounting({ library,
        onLoading: loading => { document.querySelector('[data-spoken-loading]').hidden = !loading; },
        onUse: (id, buffers) => {
            spokenId = id;
            clock.setSpokenBuffers(buffers);
            voiceRecorder?.remember('spoken');
            applySettings({ ...settings, sound: `spoken:${id}` });
        },
        onError: id => {
            const option = [...soundInput.options].find(option => option.value === `spoken:${id}`);
            if (option) option.disabled = true;
            const button = soundMenuRoot.querySelector(`[data-sound-value="spoken:${id}"]`);
            if (button) button.disabled = true;
            document.querySelector('[data-spoken-error]').textContent = 'Voice unavailable. Using Click.';
            document.querySelector('[data-spoken-feedback]').hidden = false;
            applySettings({ ...settings, sound: 'click' });
        } });
    if (preferredSpoken && library.voices.has(preferredSpoken)) {
        restoreSpoken = () => { restoreSpoken = null; spokenCounting.select(preferredSpoken); };
    }

    soundMenu = new SoundMenu({ root: soundMenuRoot, input: soundInput,
        onSelect: value => { soundInput.value = value; soundInput.dispatchEvent(new Event('change', { bubbles: true })); },
        onPlay: id => voiceRecorder?.previewRecording(id),
        onEdit: id => voiceRecorder?.edit(id),
        onDelete: id => voiceRecorder?.confirmDelete(id),
        onRecord: () => voiceRecorder?.record(),
        onClose: () => voiceRecorder?.stopPreview() });
    soundMenu.render();
}
const voiceRoot = document.querySelector('[data-voice-recorder]');
if (voiceRoot) {
    voiceRecorder = new VoiceRecorder({ root: voiceRoot, dialog: settingsPanel, clock, soundInput,
        getBeats: () => settings.beats,
        onUpdate: recorder => { soundMenu?.render(recorder); syncVoiceEffects(); },
        useSound: (sound, persist = true) => {
            spokenCounting?.cancel();
            restoreSpoken = null;
            applySettings({ ...settings, sound }, persist);
        } });
}

if (settingsPanel.querySelector('[data-settings-page]')) {
    settingsPages = new SettingsPages(settingsPanel, () => {
        soundMenu?.close();
        voiceRecorder?.cancel();
    });
    settingsPanel.addEventListener('click', event => {
        if (event.target.closest('[data-voice-start], [data-sound-empty]')) {
            spokenCounting?.cancel();
            restoreSpoken = null;
        }
    }, true);
    applySettings(settings, false);
    if (voiceRoot && new URLSearchParams(window.location.search).get('record-voice') === '1') {
        settingsPages.show('sound', false);
        settingsButton.click();
    }
}

function syncVoiceEffects() {
    if (!voiceEffects) return;
    const source = settings.sound === 'my-voice' ? clock.voiceBuffers
        : settings.sound.startsWith('spoken:') ? clock.spokenBuffers : null;
    voiceEffects.setSource(source);
}

if (soundMenuRoot && settingsPages) {
    let effectContext;
    voiceEffects = new VoiceEffectSelection({
        process: (buffers, effect) => processVoiceInWorker(buffers, effect, soundMenuRoot.dataset.voiceWorker,
            (channels, length, rate) => {
                const OfflineContext = window.OfflineAudioContext || window.webkitOfflineAudioContext;
                if (!effectContext && OfflineContext) effectContext = new OfflineContext(channels, 1, rate);
                return (effectContext || clock.getContext()).createBuffer(channels, length, rate);
            }),
        useBuffers: buffers => clock.setVoiceEffectBuffers(buffers),
        onChange: (effect, loading, available) => {
            document.querySelector('[data-effect-entry]').hidden = !available;
            document.querySelector('[data-effect-loading]').hidden = !loading;
            settingsPages.summary('effects', effects[effect].label);
            document.querySelectorAll('[data-choice-buttons="voice-effect"] [data-choice]').forEach(button => {
                const selected = button.dataset.choice === effect;
                button.setAttribute('aria-pressed', String(selected));
                button.classList.toggle('btn-primary', selected);
                button.classList.toggle('btn-secondary', !selected);
            });
        },
        onError: () => {
            document.querySelector('[data-effect-feedback]').hidden = false;
            document.querySelector('[data-effect-error]').textContent = 'Effect unavailable. Using your regular voice.';
        }
    });
    document.querySelectorAll('[data-choice-buttons="voice-effect"] [data-choice]').forEach(button => {
        button.addEventListener('click', () => {
            document.querySelector('[data-effect-feedback]').hidden = true;
            const select = () => voiceEffects.select(button.dataset.choice);
            if (voiceRecorder) voiceRecorder.previewEffect(select);
            else select();
        });
    });
    syncVoiceEffects();
}
