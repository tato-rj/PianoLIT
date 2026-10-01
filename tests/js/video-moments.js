const assert = require('assert');
const fs = require('fs');
const vm = require('vm');

module.exports = async function () {
    const root = {};
    vm.runInNewContext(fs.readFileSync('resources/js/views/video-moments.js', 'utf8'), {window: root});
    const guide = root.VideoMoments;
    assert.strictEqual(guide.formatTime(28.2), '0:28');
    assert.strictEqual(guide.formatTime(83), '1:23');
    assert.strictEqual(guide.formatTime(3723.5), '1:02:03');
    const moments = [
        {id: 1, start_time: 12, end_time: 40, title: '<Opening>', comment: 'Main idea'},
        {id: 2, start_time: 28.2, end_time: 35, title: 'Left hand', comment: 'Accompaniment'},
        {id: 3, start_time: 47, end_time: null, title: 'Second theme', comment: 'New idea'},
        {id: 4, start_time: 50, end_time: null, title: 'Return', comment: 'Opening returns'}
    ];
    assert.strictEqual(guide.activeAt(moments, 11.99, 8), null);
    assert.strictEqual(guide.activeAt(moments, 12, 8), moments[0]);
    assert.strictEqual(guide.activeAt(moments, 28.2, 8), moments[1]);
    assert.strictEqual(guide.activeAt(moments, 35, 8), moments[1]);
    assert.strictEqual(guide.activeAt(moments, 35.01, 8), moments[0]);
    assert.strictEqual(guide.activeAt(moments, 40.01, 8), null);
    assert.strictEqual(guide.activeAt(moments, 49.99, 8), moments[2]);
    assert.strictEqual(guide.activeAt(moments, 50, 8), moments[3]);
    assert.strictEqual(guide.activeAt(moments, 58, 8), moments[3]);
    assert.strictEqual(guide.activeAt(moments, 58.01, 8), null);
    assert.strictEqual(guide.activeAt(moments, 13, 8), moments[0], 'Backward seeking recalculates the correct moment');

    function element(attributes = {}) {
        const listeners = {};
        const classes = new Set();
        return {
            attrs: {...attributes}, hidden: false, children: [], listeners,
            getAttribute(name) { return this.attrs[name] ?? null; },
            setAttribute(name, value) { this.attrs[name] = value; },
            removeAttribute(name) { delete this.attrs[name]; },
            addEventListener(type, callback) { (listeners[type] || (listeners[type] = [])).push(callback); },
            removeEventListener(type, callback) { listeners[type] = (listeners[type] || []).filter(item => item !== callback); },
            emit(type, extra = {}) { (listeners[type] || []).forEach(callback => callback({target: this, stopPropagation() {}, preventDefault() {}, ...extra})); },
            appendChild(child) { this.children.push(child); },
            contains(child) { return this === child || this.children.some(item => item.contains(child)); },
            focus() { doc.activeElement = this; },
            remove() { this.removed = true; },
            closest() { return this; },
            classList: {toggle(name, enabled) { if (enabled) classes.add(name); else classes.delete(name); }, contains(name) { return classes.has(name); }}
        };
    }
    const rows = moments.map(moment => element({'data-moment-id': String(moment.id)}));
    const section = element({'data-moments-for': 'video'});
    section.children = rows;
    section.querySelectorAll = () => rows;
    let overlay;
    const doc = element();
    doc.querySelectorAll = () => [section];
    doc.createElement = () => {
        overlay = element();
        const nodes = {};
        ['about', 'popover', 'timestamp', 'title', 'comment', 'close'].forEach(name => { nodes['.piece-moments__' + name] = element(); });
        nodes['.piece-moments__about'].hidden = nodes['.piece-moments__popover'].hidden = true;
        nodes['.piece-moments__popover'].children = [nodes['.piece-moments__close']];
        overlay.children = Object.values(nodes);
        overlay.querySelector = name => nodes[name];
        return overlay;
    };
    const media = element({'data-video-moments': JSON.stringify(moments), 'data-moment-window': '8'});
    media.ownerDocument = doc;
    media.id = 'video';
    const options = guide.markerOptions(media);
    assert.strictEqual(options.markers.enabled, true);
    assert.strictEqual(options.markers.points[1].time, 28.2);
    assert.strictEqual(options.markers.points[0].label, '&lt;Opening&gt;');
    assert.strictEqual(Object.keys(guide.markerOptions(element())).length, 0);
    const zero = guide.markerOptions(element({'data-video-moments': JSON.stringify([{start_time: 0, title: 'Start'}])}));
    assert.strictEqual(zero.markers.points[0].time, Number.EPSILON);

    const container = element();
    const markers = moments.map(() => element());
    container.querySelectorAll = () => markers;
    const events = {};
    let playCount = 0;
    const player = {
        currentTime: 0, duration: 90, elements: {container, original: element(), buttons: {play: element()}},
        on(type, callback) { (events[type] || (events[type] = [])).push(callback); },
        emit(type) { (events[type] || []).forEach(callback => callback({type})); },
        play() { playCount++; return Promise.reject(new Error('Playback blocked')); }
    };
    guide.attach(player, media);
    const about = overlay.querySelector('.piece-moments__about');
    const popover = overlay.querySelector('.piece-moments__popover');
    const title = overlay.querySelector('.piece-moments__title');
    assert.strictEqual(about.hidden, true);
    player.currentTime = 13;
    player.emit('timeupdate');
    assert.strictEqual(about.hidden, false);
    assert(rows[0].classList.contains('is-active'));
    about.emit('click');
    assert.strictEqual(popover.hidden, false);
    assert.strictEqual(overlay.querySelector('.piece-moments__timestamp').textContent, '0:12');
    assert.strictEqual(title.textContent, '<Opening>', 'Commentary is assigned as text');
    assert.strictEqual(playCount, 0, 'Opening commentary does not pause or start playback');
    player.currentTime = 28.2;
    player.emit('timeupdate');
    assert.strictEqual(popover.hidden, false, 'Consecutive moments retain the expanded details');
    assert.strictEqual(title.textContent, 'Left hand');
    assert.strictEqual(overlay.querySelector('.piece-moments__comment').textContent, 'Accompaniment');
    assert.strictEqual(doc.activeElement, overlay.querySelector('.piece-moments__close'), 'Updating details preserves focus');
    player.currentTime = 41;
    player.emit('timeupdate');
    assert.strictEqual(popover.hidden, true, 'Gaps hide details');
    assert.strictEqual(about.hidden, true, 'Gaps also hide the trigger');
    player.currentTime = 47;
    player.emit('timeupdate');
    assert.strictEqual(popover.hidden, false, 'Details reopen automatically after a gap');
    assert.strictEqual(title.textContent, 'Second theme');
    player.emit('pause');
    player.emit('play');
    assert.strictEqual(popover.hidden, false, 'Pause and resume keep the reading preference');
    doc.emit('click', {target: container});
    doc.emit('click', {target: rows[2]});
    assert.strictEqual(popover.hidden, false, 'Player and list interactions keep details open');
    doc.emit('keydown', {key: 'Escape'});
    assert.strictEqual(popover.hidden, true);
    assert.strictEqual(doc.activeElement, about);
    player.currentTime = 50;
    player.emit('timeupdate');
    assert.strictEqual(popover.hidden, true, 'Closing details restores the button for later moments');
    assert.strictEqual(about.getAttribute('aria-expanded'), 'false');
    about.emit('click');
    doc.emit('click', {target: element()});
    assert.strictEqual(popover.hidden, true);
    section.emit('click', {target: rows[1]});
    assert.strictEqual(player.currentTime, 28.2);
    assert.strictEqual(playCount, 1);
    assert(rows[1].classList.contains('is-active'));
    assert.strictEqual(rows[0].getAttribute('aria-current'), null);
    markers[0].emit('click');
    assert.strictEqual(player.currentTime, 12);
    assert(rows[0].classList.contains('is-active'));
    assert.strictEqual(playCount, 1, 'Native marker clicks preserve paused state');
    markers[1].emit('keydown', {key: 'Enter'});
    assert.strictEqual(player.currentTime, 28.2);
    about.emit('click');
    player.currentTime = 80;
    player.emit('seeked');
    assert.strictEqual(about.hidden, true);
    assert.strictEqual(popover.hidden, true);
    assert(rows.every(row => !row.classList.contains('is-active')));
    player.currentTime = 13;
    player.emit('seeking');
    assert(rows[0].classList.contains('is-active'));
    assert.strictEqual(popover.hidden, false, 'Backward seeking retains the reading preference');
    overlay.querySelector('.piece-moments__close').emit('click');
    player.currentTime = 47;
    player.emit('seeked');
    assert.strictEqual(popover.hidden, true, 'The X closes details for subsequent moments');
    about.emit('click');
    player.currentTime = 90;
    player.emit('ended');
    assert.strictEqual(popover.hidden, true, 'Ending hides details outside a moment');
    player.currentTime = 0;
    player.emit('play');
    player.currentTime = 13;
    player.emit('timeupdate');
    assert.strictEqual(popover.hidden, true, 'Replaying from the beginning resets the preference');
    about.emit('click');
    player.currentTime = 0;
    player.emit('seeked');
    player.currentTime = 13;
    player.emit('seeked');
    assert.strictEqual(popover.hidden, true, 'Seeking back to the beginning also starts a fresh run');
    player.duration = 0;
    section.emit('click', {target: rows[2]});
    assert.strictEqual(player.currentTime, 13);
    player.duration = 90;
    player.emit('loadedmetadata');
    assert.strictEqual(player.currentTime, 47, 'A pre-metadata click is applied when seeking becomes available');
    player.elements.original.emit('destroyed');
    assert.strictEqual(overlay.removed, true);
    assert.strictEqual(doc.listeners.click.length, 0);
    assert.strictEqual(doc.listeners.keydown.length, 0);
    assert.strictEqual(section.listeners.click.length, 0);
    await Promise.resolve();
    // A fresh player starts collapsed even if the previous instance had details open.
    about.emit('click');
    const disposeFresh = guide.attach(player, media);
    assert.strictEqual(overlay.querySelector('.piece-moments__popover').hidden, true);
    assert.strictEqual(overlay.querySelector('.piece-moments__about').hidden, false);
    disposeFresh();
    media.setAttribute('data-video-moments', JSON.stringify([{...moments[0], start_time: 0, end_time: 20}]));
    player.currentTime = 0;
    const disposeZero = guide.attach(player, media);
    overlay.querySelector('.piece-moments__about').emit('click');
    assert.strictEqual(overlay.querySelector('.piece-moments__popover').hidden, false);
    player.emit('play');
    assert.strictEqual(overlay.querySelector('.piece-moments__popover').hidden, true, 'Starting at zero resets details even when the active moment stays the same');
    disposeZero();
    console.log('Passed: native markers, timing/seeking sync, persistent details through transitions/gaps, close/replay/reset, metadata and cleanup.');
};
