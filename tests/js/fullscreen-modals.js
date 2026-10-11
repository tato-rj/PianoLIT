const assert = require('assert');
const initialize = require('../../resources/js/components/fullscreen-modals');

module.exports = function () {
    function page(href, readyState = 'loading') {
        const events = {}, options = {}, timers = [], modals = {}, shown = [];
        let active = null;
        const state = {pianolitCanGoBack: true, unrelated: 'retained'};
        const win = {location: {href}, history: {state, writes: 0,
            replaceState(value, title, url) { assert.strictEqual(value, state); this.writes++; win.location.href = url; }},
            setTimeout(callback) { timers.push(callback); },
            document: {readyState, addEventListener(name, handler, settings) { events[name] = handler; options[name] = settings; },
                querySelector(selector) { assert.strictEqual(selector, '.fullscreen-modal.show'); return active; },
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
        return {win, events, options, shown, modal, setActive(modal) { active = modal; }, params: () => new URL(win.location.href).searchParams,
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

    const gestures = page('https://my.pianolit.com/composers');
    let clicks = 0;
    const button = {disabled: false, click() { clicks++; }};
    const target = {closest(selector) { return selector === 'button, a' ? button : null; }};
    const background = {closest() { return null; }};
    const touch = (x = 10, y = 20) => ({clientX: x, clientY: y});
    function dispatch(type, changes) {
        const event = Object.assign({type, target, touches: [], timeStamp: 0, cancelable: true, defaultPrevented: false,
            preventDefault() { assert(this.cancelable, 'Do not cancel non-cancelable events'); this.defaultPrevented = true; },
            stopPropagation() { assert.fail('Content gesture handlers must still receive the event'); }}, changes);
        gestures.events[type](event);
        return event;
    }
    function tap(time, changes) {
        dispatch('touchstart', Object.assign({timeStamp: time, touches: [touch()]}, changes));
        return dispatch('touchend', Object.assign({timeStamp: time + 30}, changes));
    }
    const protectedTypes = ['gesturestart', 'gesturechange', 'gestureend', 'dblclick', 'wheel', 'touchstart', 'touchmove'];
    for (const type of protectedTypes) {
        assert.deepStrictEqual(gestures.options[type], {passive: false, capture: true});
        assert.strictEqual(dispatch(type, {ctrlKey: true, touches: [touch(), touch(100)]}).defaultPrevented, false,
            'Normal pages and ordinary modals keep browser zoom');
    }
    for (const id of ['composer-globe-modal', 'playlist-escore-modal', 'match-tour-modal', 'future-modal']) {
        const active = gestures.modal(id);
        gestures.setActive(active); gestures.events['shown.bs.modal']({target: active});
        for (const type of protectedTypes) {
            assert.strictEqual(dispatch(type, {ctrlKey: true, touches: [touch(), touch(100)]}).defaultPrevented, true,
                id + ' blocks page zoom while allowing the event to propagate');
        }
        assert.strictEqual(dispatch('wheel').defaultPrevented, false, 'Ordinary wheel scrolling remains available');
        assert.strictEqual(dispatch('touchstart', {touches: [touch()]}).defaultPrevented, false);
        assert.strictEqual(dispatch('touchmove', {touches: [touch(10, 80)]}).defaultPrevented, false, 'One-finger scrolling remains native');
        assert.strictEqual(dispatch('touchend').defaultPrevented, false);
        assert.strictEqual(tap(100).defaultPrevented, false, 'The first tap uses its native click');
        button.click();
        const beforeRepeat = clicks;
        assert.strictEqual(tap(200).defaultPrevented, true, 'Repeated taps cannot magnify the page');
        assert.strictEqual(clicks, beforeRepeat + 1, 'A repeated button or link tap activates exactly once');
        assert.strictEqual(tap(250, {defaultPrevented: true}).defaultPrevented, true);
        assert.strictEqual(clicks, beforeRepeat + 1, 'Already handled taps do not activate twice');
        button.disabled = true;
        tap(300); assert.strictEqual(clicks, beforeRepeat + 1, 'Disabled controls remain disabled');
        button.disabled = false;
        assert.strictEqual(tap(350, {target: background}).defaultPrevented, true, 'Repeated taps on plain content cannot zoom');
        assert.strictEqual(tap(400, {cancelable: false}).defaultPrevented, false);
        assert.strictEqual(clicks, beforeRepeat + 1, 'Non-cancelable taps do not receive a second activation');
        const field = {closest(selector) { return selector === 'button, a' ? null : {}; }};
        assert.strictEqual(tap(450, {target: field}).defaultPrevented, false, 'Native form focus, selection and pickers remain usable');
        tap(600);
        dispatch('touchstart', {timeStamp: 700, touches: [touch()]});
        dispatch('touchmove', {timeStamp: 710, touches: [touch(90, 90)]});
        assert.strictEqual(dispatch('touchend', {timeStamp: 730}).defaultPrevented, false, 'A swipe is not a double tap');
        dispatch('touchstart', {timeStamp: 800, touches: [touch()]});
        assert.strictEqual(dispatch('touchend', {timeStamp: 1300}).defaultPrevented, false, 'Long presses retain native behavior');
        tap(1400); dispatch('touchcancel');
        assert.strictEqual(tap(1450).defaultPrevented, false, 'Cancelled gestures clear the tap sequence');
        gestures.events['hidden.bs.modal']({target: active}); gestures.setActive(null);
        assert.strictEqual(dispatch('wheel', {ctrlKey: true}).defaultPrevented, false, 'Closing restores browser zoom');
        gestures.setActive(active); gestures.events['shown.bs.modal']({target: active});
        assert.strictEqual(tap(1500).defaultPrevented, false, 'Reopening never inherits a double tap');
    }
    gestures.setActive(gestures.modal('another-modal'));
    assert.strictEqual(tap(1550).defaultPrevented, false, 'Switching modals resets the tap sequence');
    console.log('Passed: fullscreen modal URL restoration and gesture protection, preserved scrolling/controls, native inputs, cancellation and modal lifecycle.');
};
