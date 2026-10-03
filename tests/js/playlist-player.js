const assert = require('assert');
const initialize = require('../../resources/js/views/playlist-player');

module.exports = async function () {
    class Element {
        constructor(attributes = {}) { this.attributes = attributes; this.events = {}; this.children = {}; this.hidden = false; this.disabled = false; this.value = 0; this.style = {setProperty() {}, removeProperty(name) { delete this[name]; }}; this.classes = new Set(); this.classList = {toggle: (name, active) => active ? this.classes.add(name) : this.classes.delete(name), add: name => this.classes.add(name), remove: name => this.classes.delete(name)}; }
        getAttribute(name) { return this.attributes[name] || null; }
        setAttribute(name, value) { this.attributes[name] = value; }
        removeAttribute(name) { delete this.attributes[name]; }
        querySelector(selector) { return this.children[selector] || (this.children[selector] = new Element()); }
        querySelectorAll(selector) { return this.children[selector] || []; }
        addEventListener(event, handler) { this.events[event] = handler; }
        removeEventListener(event, handler) { if (this.events[event] === handler) delete this.events[event]; }
        fire(event, extra = {}) { if (this.events[event]) this.events[event].call(this, extra); }
        getBoundingClientRect() {
            const visible = list.items.filter(item => !item.classes.has('is-dragging'));
            const index = visible.indexOf(this);
            const top = 100 + Math.max(0, index) * 80;
            return {height: 80, top, bottom: top + 80, left: 20, width: 600};
        }
        get nextSibling() { return list.items[list.items.indexOf(this) + 1] || null; }
        closest() { return new Element(); }
        focus() {}
        remove() { const index = list.items.indexOf(this); if (index >= 0) list.items.splice(index, 1); }
    }
    class Audio extends Element {
        constructor() { super(); this.paused = true; this.ended = false; this.currentTime = 0; this.duration = 154; recordings.push(this); }
        play() { this.paused = false; this.ended = false; this.fire('play'); return new Promise((resolve, reject) => { this.reject = reject; }); }
        pause() { const changed = !this.paused; this.paused = true; if (changed) this.fire('pause'); }
        load() {}
    }
    const recordings = [], patches = [], posts = [];
    let upgrades = 0;
    const page = new Element(), list = new Element({'data-url-reorder': '/folders/1/reorder'}), player = new Element();
    list.items = [0, 1, 2].map(index => new Element({'data-id': String(index + 1), 'data-title': 'Piece ' + index, 'data-composer': 'Composer', 'data-audio': index === 2 ? '' : '/audio-' + index, 'data-preview': index === 1 ? '10' : '0', 'data-artwork': '/art.jpg'}));
    const tracks = list.items.slice();
    list.querySelectorAll = () => list.items.filter(item => item.getAttribute('data-id'));
    Object.defineProperty(list, 'lastElementChild', {get: () => list.items[list.items.length - 1] || null});
    list.appendChild = row => { const index = list.items.indexOf(row); if (index >= 0) list.items.splice(index, 1); list.items.push(row); };
    list.insertBefore = (row, before) => { const previousIndex = list.items.indexOf(row); if (previousIndex >= 0) list.items.splice(previousIndex, 1); const index = list.items.indexOf(before); list.items.splice(index < 0 ? list.items.length : index, 0, row); };
    tracks.forEach(row => {
        row.children['[data-playlist-favorite]'] = new Element({'data-url': '/favorite/' + row.getAttribute('data-id')});
    });
    page.children['[data-playlist-tracks]'] = list;
    page.children['[data-playlist-player]'] = player;
    player.children['[data-speed]'] = [.5, .75, 1, 1.25, 1.5, 2].map(speed => new Element({'data-speed': String(speed)}));
    const doc = new Element(); doc.querySelector = () => page; doc.getElementById = () => new Element(); doc.createElement = () => new Element();
    const win = {Audio, bootstrap: {Modal: {getOrCreateInstance: () => ({show() { upgrades++; }})}}, innerHeight: 800, requestAnimationFrame() { return 1; }, cancelAnimationFrame() {}, scrollBy() {}, addEventListener() {}, axios: {
        patch(url, data) { return new Promise((resolve, reject) => patches.push({url, data, resolve, reject})); },
        post(url) { return new Promise((resolve, reject) => posts.push({url, resolve, reject})); }
    }};
    async function settle() { await new Promise(resolve => setImmediate(resolve)); }
    const result = initialize(doc, win);
    assert.strictEqual(result.time(154), '2:34');
    assert.strictEqual(recordings.length, 0, 'No recording loads or plays before user action');
    assert.strictEqual(player.hidden, true, 'The player stays hidden on page load');
    page.querySelector('[data-shuffle]').fire('click');
    assert.strictEqual(player.hidden, true, 'Changing the queue does not reveal the player');
    page.querySelector('[data-shuffle]').fire('click');
    tracks[0].querySelector('[data-track-play]').fire('click');
    assert.strictEqual(player.hidden, false, 'Playing a track reveals the player');
    const first = recordings[0];
    assert(!first.paused);
    player.querySelector('[data-player-toggle]').fire('click');
    assert(first.paused);
    player.querySelector('[data-player-toggle]').fire('click');
    assert(!first.paused);
    player.children['[data-speed]'][4].fire('click');
    assert.strictEqual(first.playbackRate, 1.5);
    tracks[1].querySelector('[data-track-play]').fire('click');
    const second = recordings[1];
    assert(first.paused);
    assert.strictEqual(second.playbackRate, 1.5, 'Speed persists when changing tracks');
    const status = page.querySelector('[data-playlist-status]');
    const previousStatus = status.textContent;
    first.reject(new Error('Late rejection'));
    await settle();
    assert.strictEqual(status.textContent, previousStatus, 'Stale play promises cannot change current status');
    first.fire('ended');
    assert.strictEqual(recordings.length, 2, 'Stale ended events cannot advance the new track');
    second.currentTime = 25;
    second.fire('seeking');
    assert(second.paused);
    assert.strictEqual(second.currentTime, 10);
    assert.strictEqual(upgrades, 1);
    second.fire('timeupdate');
    assert.strictEqual(upgrades, 1, 'Preview stop prompts only once');
    player.querySelector('[data-player-toggle]').fire('click');
    assert.strictEqual(second.currentTime, 0, 'A user can replay the preview');
    assert(!second.paused);
    second.reject(new Error('Playback unavailable'));
    await settle();
    assert(status.textContent.includes('could not start'));
    // End-of-queue does not silently wrap unless Loop All is enabled.
    second.currentTime = 0; second.ended = true; second.paused = true; second.fire('ended');
    assert.strictEqual(recordings.length, 2);
    page.querySelector('[data-loop]').fire('click');
    second.fire('ended');
    assert.strictEqual(recordings.length, 2, 'Automatic advance reuses the media element for iOS playback');
    assert.strictEqual(second.src, '/audio-0');
    page.querySelector('[data-loop]').fire('click');
    const looped = second; looped.currentTime = 154; looped.paused = true; looped.ended = true; looped.fire('ended');
    assert.strictEqual(recordings.length, 2);
    assert.strictEqual(looped.currentTime, 0);
    assert(!looped.paused, 'Loop One replays the current recording');
    page.querySelector('[data-shuffle]').fire('click');
    assert.strictEqual(page.querySelector('[data-shuffle]').getAttribute('aria-pressed'), 'true');
    tracks[1].querySelector('[data-track-handle]').fire('keydown', {key: 'ArrowUp', preventDefault() {}});
    assert.deepStrictEqual(patches[0].data.ids, [2, 1, 3]);
    patches[0].reject(new Error('Save failed')); await settle();
    assert.deepStrictEqual(list.items, tracks, 'Failed persistence restores the previous folder order');
    const grip = tracks[0].querySelector('[data-track-handle]');
    function pointer(type, y, id = 7, extra = {}) { return {type, clientY: y, pointerId: id, button: 0, preventDefault() {}, ...extra}; }
    grip.fire('pointerdown', pointer('pointerdown', 140));
    doc.fire('pointermove', pointer('pointermove', 142));
    assert(!tracks[0].classes.has('is-dragging'), 'A small movement does not start a drag');
    doc.fire('pointermove', pointer('pointermove', 330));
    assert(tracks[0].classes.has('is-dragging'));
    assert.strictEqual(tracks[0].style.transform, 'translateY(290px)', 'The lifted row follows the pointer');
    doc.fire('pointermove', pointer('pointermove', 220));
    doc.fire('pointermove', pointer('pointermove', 340));
    doc.fire('pointerup', pointer('pointerup', 340));
    assert.deepStrictEqual(patches[1].data.ids, [2, 3, 1], 'Multiple document moves keep dragging through different slots');
    assert(!tracks[0].classes.has('is-dragging'));
    assert.strictEqual(tracks[0].style.transform, undefined, 'Drop removes floating-row styles');
    assert.strictEqual(list.items.length, 3, 'Drop removes the placeholder');
    patches[1].reject(new Error('Drop save failed')); await settle();
    assert.deepStrictEqual(list.items, tracks, 'Failed drag save restores the original order');
    grip.fire('pointerdown', pointer('pointerdown', 140, 9, {pointerType: 'touch'}));
    doc.fire('pointermove', pointer('pointermove', 340, 9));
    doc.fire('pointercancel', pointer('pointercancel', 340, 9));
    assert.deepStrictEqual(list.items, tracks, 'Cancelling a touch drag restores the original order');
    assert.strictEqual(patches.length, 2, 'Cancelled drags never save');
    grip.fire('pointerdown', pointer('pointerdown', 140, 10));
    doc.fire('pointermove', pointer('pointermove', 340, 10));
    doc.fire('keydown', {key: 'Escape', preventDefault() {}});
    assert.deepStrictEqual(list.items, tracks, 'Escape cancels and restores the drag');
    grip.fire('pointerdown', pointer('pointerdown', 140, 11));
    doc.fire('pointerup', pointer('pointerup', 140, 11));
    assert.strictEqual(patches.length, 2, 'A press without a drag never saves');
    for (const pointerType of ['mouse', 'touch']) {
        const id = pointerType === 'mouse' ? 12 : 13;
        grip.fire('pointerdown', pointer('pointerdown', 140, id, {pointerType}));
        doc.fire('pointermove', pointer('pointermove', 340, id, {pointerType}));
        doc.fire('pointerup', pointer('pointerup', 340, id, {pointerType}));
        const saved = patches[patches.length - 1];
        assert.deepStrictEqual(saved.data.ids, [2, 3, 1]);
        saved.resolve({data: ''}); await settle();
        assert.deepStrictEqual(list.items, [tracks[1], tracks[2], tracks[0]], pointerType + ' drag persists the dropped order');
        grip.fire('pointerdown', pointer('pointerdown', 300, id, {pointerType}));
        doc.fire('pointermove', pointer('pointermove', 110, id, {pointerType}));
        doc.fire('pointerup', pointer('pointerup', 110, id, {pointerType}));
        const restored = patches[patches.length - 1];
        assert.deepStrictEqual(restored.data.ids, [1, 2, 3]);
        restored.resolve({data: ''}); await settle();
    }
    const favorite = tracks[0].querySelector('[data-playlist-favorite]');
    favorite.fire('click'); assert(favorite.disabled);
    posts[0].reject(new Error('Save failed')); await settle();
    assert(!favorite.disabled); assert.strictEqual(list.items.length, 3);
    favorite.fire('click'); posts[1].resolve({data: {status: false}}); await settle();
    assert.strictEqual(list.items.length, 2);
    assert.strictEqual(page.querySelector('[data-playlist-count]').textContent, '2 pieces');
    recordings.forEach(recording => recording.pause());
    initialize(doc, win);
    assert.strictEqual(player.hidden, true, 'A fresh page starts with the player hidden');
    page.querySelector('[data-play-all]').fire('click');
    assert.strictEqual(player.hidden, false, 'Play all reveals the player');
    assert(!recordings[recordings.length - 1].paused, 'Play all starts playback');
    console.log('Passed: playlist visibility, playback, preview boundaries, stale events, speed, loop, mouse/touch drag lifecycle, cancellation, reorder rollback and favorite failures.');
};
