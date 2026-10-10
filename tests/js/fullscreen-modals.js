const assert = require('assert');
const initialize = require('../../resources/js/components/fullscreen-modals');

module.exports = function () {
    function page(href, readyState = 'loading') {
        const events = {}, timers = [], modals = {}, shown = [];
        const state = {pianolitCanGoBack: true, unrelated: 'retained'};
        const win = {location: {href}, history: {state, writes: 0,
            replaceState(value, title, url) { assert.strictEqual(value, state); this.writes++; win.location.href = url; }},
            setTimeout(callback) { timers.push(callback); },
            document: {readyState, addEventListener(name, handler) { events[name] = handler; },
                getElementById(id) { return modals[id]; }},
            bootstrap: {Modal: {getOrCreateInstance(modal) { return {show() {
                shown.push(modal.id);
                if (modal.onShow) modal.onShow();
                events['shown.bs.modal']({target: modal});
            }}; }}}
        };
        function modal(id, fullscreen = true) {
            return modals[id] = {id, matches(selector) { assert.strictEqual(selector, '.fullscreen-modal'); return fullscreen; }};
        }
        initialize(win);
        return {win, events, shown, modal, params: () => new URL(win.location.href).searchParams,
            ready() { if (events.DOMContentLoaded) events.DOMContentLoaded(); },
            flush() { while (timers.length) timers.shift()(); }};
    }

    const h = page('https://my.pianolit.com/composers?continent=Europe&search=Bach#details');
    const globe = h.modal('composer-globe-modal'), normal = h.modal('delete-modal', false);
    h.ready(); h.flush();
    assert.deepStrictEqual(h.shown, [], 'The ordinary page does not auto-open a modal');
    h.events['shown.bs.modal']({target: normal});
    assert.strictEqual(h.win.history.writes, 0, 'Ordinary/confirmation modals never alter the URL');
    h.events['shown.bs.modal']({target: globe});
    assert.strictEqual(h.params().get('modal'), globe.id);
    assert.strictEqual(h.params().get('continent'), 'Europe');
    assert.strictEqual(h.params().get('search'), 'Bach');
    assert.strictEqual(new URL(h.win.location.href).hash, '#details');
    h.events['shown.bs.modal']({target: globe});
    assert.strictEqual(h.win.history.writes, 1, 'Repeated events do not create redundant writes');

    // Reload URLs for every current fullscreen shell and an unknown future one.
    for (const id of ['composer-globe-modal', 'playlist-escore-modal', 'generate-pdf-folder-17', 'match-tour-modal', 'future-modal']) {
        const restored = page('https://my.pianolit.com/collections/7?source=saved&modal=' + id + '#tracks');
        const modal = restored.modal(id);
        restored.ready();
        assert.strictEqual(restored.shown.length, 0, 'Wait for other DOMContentLoaded handlers');
        let initialized = false;
        modal.onShow = () => { assert(initialized, 'Page-specific lifecycle listeners are ready before restoration'); };
        initialized = true;
        restored.flush();
        assert.deepStrictEqual(restored.shown, [id]);
        assert.strictEqual(restored.win.history.writes, 0, 'Restoration retains the current history entry');
        restored.events['hidden.bs.modal']({target: modal});
        assert.strictEqual(restored.win.location.href, 'https://my.pianolit.com/collections/7?source=saved#tracks');
    }
    const second = h.modal('future-modal');
    h.events['shown.bs.modal']({target: second});
    h.events['hidden.bs.modal']({target: globe});
    h.events['hidden.bs.modal']({target: normal});
    assert.strictEqual(h.params().get('modal'), second.id, 'A closing old/ordinary modal cannot erase the active modal');
    h.events['hidden.bs.modal']({target: second});
    assert.strictEqual(h.params().has('modal'), false);

    for (const id of ['missing', 'delete-modal', '<script>', '__proto__', 'playlist-escore-modal']) {
        const invalid = page('https://my.pianolit.com/collections/7?source=saved&modal=' + encodeURIComponent(id));
        invalid.modal('delete-modal', false);
        invalid.ready(); invalid.flush();
        assert.deepStrictEqual(invalid.shown, [], 'Never open missing, unauthorized or ordinary modal markup');
        assert.strictEqual(invalid.params().has('modal'), false);
        assert.strictEqual(invalid.params().get('source'), 'saved');
    }
    const late = page('https://my.pianolit.com/composers?modal=composer-globe-modal', 'complete');
    late.modal('composer-globe-modal'); late.flush();
    assert.deepStrictEqual(late.shown, ['composer-globe-modal'], 'Also supports scripts initialized after DOM ready');
    late.win.history.replaceState = () => { throw new Error('Blocked'); };
    assert.doesNotThrow(() => late.events['hidden.bs.modal']({target: late.modal('composer-globe-modal')}));
    console.log('Passed: fullscreen modal URL opening, deferred restoration, closing, future shells, ordinary/unavailable exclusions and preserved query/hash/history state.');
};
