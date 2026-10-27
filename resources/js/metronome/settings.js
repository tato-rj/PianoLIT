import { DEFAULT_TEMPO, MIN_TEMPO, MAX_TEMPO } from './gesture';

export const SOUND_PROFILES = {
    click: { type: 'triangle', frequency: 1400, attack: 0.0005, duration: 0.03, volume: 0.95, ratios: [1] },
    woodblock: { type: 'sine', frequency: 800, attack: 0.0005, duration: 0.06, volume: 0.95, ratios: [1, 2.76] },
    beep: { type: 'sine', frequency: 1000, attack: 0.002, hold: 0.025, duration: 0.06, volume: 0.85, ratios: [1] },
    cowbell: { type: 'square', frequency: 540, attack: 0.0005, duration: 0.07, volume: 0.7, ratios: [1, 1.48] },
};

export const DEFAULT_SETTINGS = {
    beats: 1, color: '#0055fe', sound: 'click', type: 'classic', showPath: false, conductorMarker: 'dot', font: 'nunito',
};
export const SETTINGS_KEY = 'metronome.settings.v1';
export const TEMPO_KEY = 'metronome.tempo.v1';

export function loadTempo(storage) {
    try {
        const value = JSON.parse(storage.getItem(TEMPO_KEY));
        return Number.isInteger(value) && value >= MIN_TEMPO && value <= MAX_TEMPO
            ? value : DEFAULT_TEMPO;
    } catch (error) {
        return DEFAULT_TEMPO;
    }
}

export function normalizeSettings(value) {
    const input = value && typeof value === 'object' ? value : {};
    return {
        beats: [1, 2, 3, 4].includes(Number(input.beats)) ? Number(input.beats) : DEFAULT_SETTINGS.beats,
        color: typeof input.color === 'string' && /^#[0-9a-f]{6}$/i.test(input.color)
            ? input.color.toLowerCase() : DEFAULT_SETTINGS.color,
        sound: input.sound === 'my-voice' || Object.prototype.hasOwnProperty.call(SOUND_PROFILES, input.sound) ? input.sound : DEFAULT_SETTINGS.sound,
        type: input.type === 'conductor' ? 'conductor' : 'classic',
        showPath: typeof input.showPath === 'boolean' ? input.showPath : DEFAULT_SETTINGS.showPath,
        conductorMarker: input.conductorMarker === 'hand' ? 'hand' : 'dot',
        font: ['nunito', 'outfit', 'playfair', 'pattaya', 'sail'].includes(input.font) ? input.font : DEFAULT_SETTINGS.font,
    };
}

export function loadSettings(storage) {
    try {
        return normalizeSettings(JSON.parse(storage.getItem(SETTINGS_KEY)));
    } catch (error) {
        return { ...DEFAULT_SETTINGS };
    }
}

function luminance(channels) {
    const linear = channels.map(channel => {
        const value = channel / 255;
        return value <= 0.04045 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4;
    });
    return linear[0] * 0.2126 + linear[1] * 0.7152 + linear[2] * 0.0722;
}

function hexColor(channels) {
    return `#${channels.map(channel => channel.toString(16).padStart(2, '0')).join('')}`;
}

function mixColor(from, to, amount) {
    return from.map((channel, index) => Math.round(channel + (to[index] - channel) * amount));
}

export function colorPalette(color) {
    const channels = color.slice(1).match(/../g).map(channel => parseInt(channel, 16));
    return {
        pulse: hexColor(mixColor(channels, [0, 0, 0], 0.42)),
        ink: luminance(channels) > 0.4 ? '#112037' : '#ffffff',
        headerInk: luminance(channels) > 0.179 ? '#000000' : '#ffffff',
    };
}
