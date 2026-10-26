export const GAME_SETTINGS_KEY = 'pianolit.games.game.settings.v1';

function readSettings(getStorage) {
    try {
        const saved = JSON.parse(getStorage().getItem(GAME_SETTINGS_KEY));
        return saved && typeof saved === 'object' && !Array.isArray(saved) ? saved : {};
    } catch (_) { return {}; }
}

function validValue(value) {
    return typeof value === 'boolean' || (typeof value === 'number' && Number.isFinite(value)) || (typeof value === 'string' && value.length <= 2000);
}

function cleanOptions(options, defaults = options) {
    if (!options || typeof options !== 'object' || Array.isArray(options)) return null;
    const entries = Object.entries(options).filter(([name, value]) =>
        Object.hasOwn(defaults, name) && (validValue(value) || (Array.isArray(value) && value.length <= 128 && value.every(validValue)))
    );
    return entries.length ? Object.fromEntries(entries) : null;
}

export function rememberGameSettings(game, options, getStorage = () => window.localStorage) {
    const clean = cleanOptions(options);
    if (!clean) return false;
    try {
        getStorage().setItem(GAME_SETTINGS_KEY, JSON.stringify({...readSettings(getStorage), [game]: clean}));
        return true;
    } catch (_) { return false; }
}

export function savedGameSettings(game, defaults, getStorage = () => window.localStorage) {
    const saved = cleanOptions(readSettings(getStorage)[game], defaults);
    return saved ? {...defaults, ...saved} : null;
}

export function replayGameUrl(url, options) {
    const params = new URLSearchParams();
    Object.entries(options).forEach(([name, value]) => {
        if (Array.isArray(value)) {
            // An explicit empty selection must not silently restore the server's defaults.
            (value.length ? value : ['']).forEach(item => params.append(`${name}[]`, String(item)));
        } else {
            params.set(name, typeof value === 'boolean' ? (value ? '1' : '0') : String(value));
        }
    });
    return `${url.split('?')[0]}?${params}`;
}

export function restoreGameSettingsForm(form, options) {
    [...form.elements].forEach(input => {
        const name = input.name?.replace(/\[\]$/, '');
        if (!name || !Object.hasOwn(options, name) || input.type === 'hidden') return;
        const value = options[name];
        if (input.type === 'checkbox' || input.type === 'radio') {
            input.checked = Array.isArray(value) ? value.map(String).includes(input.value) :
                input.type === 'radio' ? String(value) === input.value : !!value && value !== '0';
            if (input.hasAttribute('multichoice') || input.hasAttribute('singlechoice')) {
                const label = input.parentElement.querySelector('label');
                label?.classList.toggle('btn-secondary', input.checked);
                label?.classList.toggle('btn-white', !input.checked);
            }
        } else {
            input.value = String(value);
            if (input.type === 'range') input.dispatchEvent(new Event('input', {bubbles: true}));
        }
    });
}

export function refreshGameCards(root = document, getStorage = () => window.localStorage) {
    root.querySelectorAll('[data-game-card]').forEach(card => {
        let defaults;
        try { defaults = JSON.parse(card.dataset.gameDefaults); } catch (_) { return; }
        const saved = savedGameSettings(card.dataset.gameCard, defaults, getStorage);
        card.querySelector('[data-card-setup]').classList.toggle('d-none', !!saved);
        card.querySelector('[data-card-replay]').classList.toggle('d-none', !saved);
        card.querySelector('[data-card-settings]').classList.toggle('d-none', !saved);
        const form = card.querySelector('form[method="GET"]');
        if (form) restoreGameSettingsForm(form, saved || defaults);
        const replay = card.querySelector('[data-card-replay]');
        if (saved) replay.href = replayGameUrl(card.dataset.gameUrl, saved);
        else replay.removeAttribute('href');
    });
}
