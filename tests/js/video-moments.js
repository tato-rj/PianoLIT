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
        {id: 3, start_time: 47, end_time: null, title: 'Second theme', comment: ''},
        {id: 4, start_time: 50, end_time: null, title: 'Return', comment: 'Opening returns'}
    ];
    assert.strictEqual(guide.activeAt(moments, 11.99), null);
    assert.strictEqual(guide.activeAt(moments, 12), moments[0]);
    assert.strictEqual(guide.activeAt(moments, 28.2), moments[1]);
    assert.strictEqual(guide.activeAt(moments, 35), moments[0]);
    assert.strictEqual(guide.activeAt(moments, 35.01), moments[0]);
    assert.strictEqual(guide.activeAt(moments, 40.01), null);
    assert.strictEqual(guide.activeAt(moments, 49.99), moments[2]);
    assert.strictEqual(guide.activeAt(moments, 50), moments[3]);
    assert.strictEqual(guide.activeAt(moments, 58), moments[3]);
    assert.strictEqual(guide.activeAt(moments, 58.01), moments[3]);
    assert.strictEqual(guide.activeAt(moments, 90), moments[3], 'An open-ended moment lasts through the end of the piece');
    assert.strictEqual(guide.activeAt(moments, 40), null, 'An explicit end expires at that exact time');
    const overlap = [{id: 1, start_time: 0, end_time: null}, {id: 2, start_time: 20, end_time: 30}];
    assert.strictEqual(guide.activeAt(overlap, 25), overlap[1]);
    assert.strictEqual(guide.activeAt(overlap, 30), overlap[0], 'An open-ended moment remains valid after a later finite moment ends');
    assert.strictEqual(guide.activeAt(moments, 13), moments[0], 'Backward seeking recalculates the correct moment');

    function element(attributes = {}) {
        const listeners = {};
        const classes = new Set();
        return {
            attrs: {...attributes}, hidden: false, children: [], listeners, style: {},
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
    section.closest = () => null;
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
    const media = element({'data-video-moments': JSON.stringify(moments)});
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
    assert(overlay.innerHTML.includes('>ⓘ About this section</button>'));
    assert(overlay.innerHTML.includes('aria-label="Close section commentary"'));
    player.currentTime = 13;
    player.emit('loadedmetadata');
    player.emit('seeked');
    player.emit('timeupdate');
    assert.strictEqual(about.hidden, true, 'Seeking into a moment before playback keeps the button hidden');
    player.emit('play');
    assert.strictEqual(about.hidden, true, 'A play request does not reveal the guide before playback actually starts');
    player.emit('playing');
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
    assert.strictEqual(overlay.querySelector('.piece-moments__comment').hidden, true, 'Title-only moments hide the empty commentary');
    player.emit('pause');
    assert.strictEqual(about.hidden, false, 'After playback starts, pausing keeps the active guide available');
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
    assert.strictEqual(overlay.querySelector('.piece-moments__comment').hidden, false, 'Commentary returns for a later moment with a description');
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
    assert.strictEqual(about.hidden, false);
    assert.strictEqual(popover.hidden, false);
    assert.strictEqual(title.textContent, 'Return');
    assert(rows[3].classList.contains('is-active'), 'Open-ended moments remain selected beyond eight seconds');
    player.currentTime = 13;
    player.emit('seeking');
    assert(rows[0].classList.contains('is-active'));
    assert.strictEqual(popover.hidden, false, 'Backward seeking retains the reading preference');
    overlay.querySelector('.piece-moments__close').emit('click');
    player.currentTime = 47;
    player.emit('seeked');
    assert.strictEqual(popover.hidden, true, 'The X closes details for subsequent moments');
    about.emit('click');
    player.currentTime = 87.999;
    player.emit('timeupdate');
    assert.strictEqual(overlay.hidden, false, 'The overlay remains available before the final two seconds');
    player.currentTime = 88;
    player.emit('timeupdate');
    assert.strictEqual(overlay.hidden, true, 'The whole overlay hides exactly two seconds before the end');
    assert.strictEqual(overlay.style.display, 'none');
    assert.strictEqual(popover.hidden, true);
    assert.strictEqual(about.hidden, true);
    assert.strictEqual(about.getAttribute('aria-expanded'), 'false');
    assert.strictEqual(doc.activeElement, player.elements.buttons.play, 'Focus leaves the hidden overlay');
    player.emit('pause');
    assert.strictEqual(overlay.hidden, true, 'Pausing near the end does not restore the overlay');
    player.currentTime = 87;
    player.emit('seeked');
    assert.strictEqual(overlay.hidden, false, 'Seeking out of the final two seconds restores the overlay');
    assert.strictEqual(overlay.style.display, '');
    assert.strictEqual(popover.hidden, false, 'Seeking back preserves the reading preference');
    overlay.querySelector('.piece-moments__close').emit('click');
    player.currentTime = 88;
    player.emit('seeked');
    assert.strictEqual(doc.activeElement, player.elements.buttons.play, 'Focus also leaves a hidden About button');
    player.currentTime = 87;
    player.emit('seeked');
    about.emit('click');
    player.currentTime = 90;
    player.emit('ended');
    assert.strictEqual(overlay.hidden, true, 'The overlay stays hidden after the video ends');
    assert(rows[3].classList.contains('is-active'), 'Hiding the overlay does not change open-ended moment validity');
    player.duration = 0;
    player.emit('durationchange');
    assert.strictEqual(overlay.hidden, false, 'Unknown duration does not hide the overlay');
    player.duration = Infinity;
    player.emit('durationchange');
    assert.strictEqual(overlay.hidden, false, 'An indefinite duration has no final-two-second cutoff');
    player.duration = 90;
    player.emit('durationchange');
    assert.strictEqual(overlay.hidden, true, 'Available metadata applies the cutoff');
    player.currentTime = 0;
    player.emit('play');
    assert.strictEqual(overlay.hidden, false, 'Replaying restores the overlay');
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
    assert.strictEqual(overlay.querySelector('.piece-moments__about').hidden, true, 'A fresh paused player hides the button');
    player.emit('playing');
    assert.strictEqual(overlay.querySelector('.piece-moments__about').hidden, false);
    disposeFresh();
    media.setAttribute('data-video-moments', JSON.stringify([{...moments[0], start_time: 0, end_time: 20}]));
    player.currentTime = 0;
    const disposeZero = guide.attach(player, media);
    assert.strictEqual(overlay.querySelector('.piece-moments__about').hidden, true, 'A moment starting at zero stays hidden before Play');
    player.emit('loadedmetadata');
    player.emit('play');
    assert.strictEqual(overlay.querySelector('.piece-moments__about').hidden, true);
    player.emit('playing');
    assert.strictEqual(overlay.querySelector('.piece-moments__about').hidden, false);
    overlay.querySelector('.piece-moments__about').emit('click');
    assert.strictEqual(overlay.querySelector('.piece-moments__popover').hidden, false);
    player.emit('play');
    assert.strictEqual(overlay.querySelector('.piece-moments__popover').hidden, true, 'Starting at zero resets details even when the active moment stays the same');
    disposeZero();
    player.playing = true;
    const disposePlaying = guide.attach(player, media);
    assert.strictEqual(overlay.querySelector('.piece-moments__about').hidden, false, 'Attaching to an already playing player reveals the active moment');
    disposePlaying();

    // About's stacked list follows the rendered player, including resize/tab changes.
    const heights = {};
    section.closest = () => element();
    section.style = {
        setProperty(name, value) { heights[name] = value; },
        removeProperty(name) { delete heights[name]; }
    };
    let videoHeight = 202.5;
    container.getBoundingClientRect = () => ({height: videoHeight});
    let resize;
    let disconnected = false;
    root.ResizeObserver = class {
        constructor(callback) { resize = callback; }
        observe(target) { assert.strictEqual(target, container); }
        disconnect() { disconnected = true; }
    };
    const disposeSized = guide.attach(player, media);
    assert.strictEqual(heights['--piece-moments-video-height'], '202.5px');
    videoHeight = 306.5625;
    resize();
    assert.strictEqual(heights['--piece-moments-video-height'], '306.5625px');
    videoHeight = 0;
    resize();
    assert.strictEqual(heights['--piece-moments-video-height'], '306.5625px', 'A hidden tab preserves the last visible height');
    player.fullscreen = {active: true};
    videoHeight = 900;
    resize();
    assert.strictEqual(heights['--piece-moments-video-height'], '306.5625px', 'Fullscreen does not enlarge the inline list');
    player.fullscreen.active = false;
    videoHeight = 180;
    resize();
    assert.strictEqual(heights['--piece-moments-video-height'], '180px');
    disposeSized();
    assert(disconnected);
    assert.strictEqual(heights['--piece-moments-video-height'], undefined);
    delete root.ResizeObserver;
    const resizeListeners = element();
    doc.defaultView = resizeListeners;
    const disposeFallback = guide.attach(player, media);
    videoHeight = 225;
    resizeListeners.emit('resize');
    assert.strictEqual(heights['--piece-moments-video-height'], '225px', 'Older browsers update the cap on window resize');
    disposeFallback();
    assert.strictEqual(resizeListeners.listeners.resize.length, 0);
    console.log('Passed: native markers, timing/seeking sync, persistent details through transitions/gaps, close/replay/reset, metadata and cleanup.');
};
