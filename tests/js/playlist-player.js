const assert = require('assert');
const initialize = require('../../resources/js/views/playlist-player');

module.exports = async function () {
    class Element {
        constructor(attributes = {}) { this.attributes = attributes; this.events = {}; this.children = {}; this.hidden = false; this.disabled = false; this.value = 0; this.style = {setProperty() {}, removeProperty(name) { delete this[name]; }}; this.classes = new Set(); this.classList = {toggle: (name, active) => active ? this.classes.add(name) : this.classes.delete(name), add: name => this.classes.add(name), remove: name => this.classes.delete(name)}; }
        getAttribute(name) { return this.attributes[name] || null; }
        setAttribute(name, value) { this.attributes[name] = value; }
        removeAttribute(name) { delete this.attributes[name]; }
        appendChild(child) { (this.nodes || (this.nodes = [])).push(child); }
        querySelector(selector) { return this.children[selector] || (this.children[selector] = new Element()); }
        querySelectorAll(selector) { return this.children[selector] || []; }
        addEventListener(event, handler, options) { this.events[event] = handler; (this.listenerOptions || (this.listenerOptions = {}))[event] = options; }
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
        get src() { return this.attributes.src || ''; }
        set src(value) { this.attributes.src = value; }
        play() { this.paused = false; this.ended = false; this.fire('play'); return new Promise((resolve, reject) => { this.reject = reject; }); }
        pause() { const changed = !this.paused; this.paused = true; if (changed) this.fire('pause'); }
        load() {}
    }
    function sourceUrl(media) { return media.playlistSource.getAttribute('src') || ''; }
    const recordings = [], patches = [], posts = [];
    let upgrades = 0;
    const page = new Element(), list = new Element({'data-url-reorder': '/folders/1/reorder'}), player = new Element();
    list.items = [0, 1, 2].map(index => new Element({'data-id': String(index + 1), 'data-title': 'Piece ' + index, 'data-composer': 'Composer', 'data-audio': index === 2 ? '' : index === 0 ? '/legacy.MPGA?version=1' : '/recording.mp4', 'data-preview': index === 1 ? '10' : '0', 'data-artwork': '/art.jpg'}));
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
    const doc = new Element(); doc.querySelector = selector => selector === '[data-playlist-page]' ? page : null; doc.getElementById = () => new Element(); doc.createElement = () => new Element();
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
    const firstRejection = first.reject, firstEnded = first.events.ended;
    assert(!first.paused);
    assert.strictEqual(first.getAttribute('src'), null, 'A direct src must not override the typed source');
    assert.strictEqual(sourceUrl(first), '/legacy.MPGA?version=1');
    assert.strictEqual(first.playlistSource.getAttribute('type'), 'audio/mpeg', 'Legacy MPGA recordings declare MP3 for Safari');
    first.currentTime = 4;
    doc.fire('show.bs.offcanvas', {target: new Element({'id': 'folder-options'})});
    assert(player.hidden, 'Opening an offcanvas hides the folder player before its transition');
    assert(first.paused, 'Opening an offcanvas pauses folder audio');
    assert.strictEqual(first.currentTime, 4, 'Closing for a panel preserves the playback position');
    first.fire('ended');
    assert(player.hidden, 'A queued ended event cannot reopen the player behind a panel');
    assert.strictEqual(sourceUrl(first), '/legacy.MPGA?version=1', 'A closed player cannot advance the queue');
    doc.fire('hidden.bs.offcanvas');
    assert(player.hidden, 'Closing the panel does not restart playback');
    tracks[0].querySelector('[data-track-play]').fire('click');
    assert(!player.hidden && !first.paused, 'The current folder track reopens and resumes the band');
    assert.strictEqual(recordings.length, 1, 'Offcanvas dismissal retains the existing media element');

    player.querySelector('[data-player-toggle]').fire('click');
    assert(first.paused);
    player.querySelector('[data-player-toggle]').fire('click');
    assert(!first.paused);
    player.children['[data-speed]'][4].fire('click');
    assert.strictEqual(first.playbackRate, 1.5);
    tracks[1].querySelector('[data-track-play]').fire('click');
    const second = recordings[0];
    assert.strictEqual(recordings.length, 1, 'Manual track changes reuse the authorized audio element');
    assert.strictEqual(sourceUrl(second), '/recording.mp4');
    assert.strictEqual(second.playlistSource.getAttribute('type'), 'video/mp4', 'Mixed-format playlists update the source type');
    assert.deepStrictEqual(second.nodes, [second.playlistSource], 'Track changes retain one source child');
    assert.strictEqual(second.playbackRate, 1.5, 'Speed persists when changing tracks');
    const status = page.querySelector('[data-playlist-status]');
    const previousStatus = status.textContent;
    firstRejection(new Error('Late rejection'));
    await settle();
    assert.strictEqual(status.textContent, previousStatus, 'Stale play promises cannot change current status');
    firstEnded();
    assert.strictEqual(sourceUrl(second), '/recording.mp4', 'Stale ended handlers cannot advance the new track');
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
    assert.strictEqual(recordings.length, 1);
    page.querySelector('[data-loop]').fire('click');
    second.fire('ended');
    assert.strictEqual(recordings.length, 1, 'Automatic advance reuses the media element for iOS playback');
    assert.strictEqual(sourceUrl(second), '/legacy.MPGA?version=1');
    page.querySelector('[data-loop]').fire('click');
    const looped = second; looped.currentTime = 154; looped.paused = true; looped.ended = true; looped.fire('ended');
    assert.strictEqual(recordings.length, 1);
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

    // Exercise delayed iOS metadata and a background loader racing a user tap.
    recordings.forEach(recording => recording.pause());
    const timers = new Map(); let timerId = 0, observer, hiddenPage;
    win.setTimeout = callback => { timers.set(++timerId, callback); return timerId; };
    win.clearTimeout = id => timers.delete(id);
    win.addEventListener = (event, callback) => { if (event === 'pagehide') hiddenPage = callback; };
    win.IntersectionObserver = class {
        constructor(callback) { this.callback = callback; this.disconnected = false; observer = this; }
        observe() {}
        unobserve() {}
        disconnect() { this.disconnected = true; }
    };
    tracks[0].attributes['data-audio'] = '/legacy.MPGA?version=1';
    list.items = tracks.slice();
    initialize(doc, win);
    const initialCount = recordings.length;
    observer.callback(tracks.slice(0, 2).map(target => ({target, isIntersecting: true})));
    assert.strictEqual(recordings.length, initialCount + 1, 'Metadata reads use a single retained audio element');
    const metadata = recordings[initialCount];
    assert.strictEqual(metadata.playlistSource.getAttribute('type'), 'audio/mpeg', 'Duration probes also type legacy MPGA');
    const staleMetadata = metadata.events.loadedmetadata;
    metadata.duration = NaN;
    metadata.fire('loadedmetadata');
    assert.strictEqual(sourceUrl(metadata), '/legacy.MPGA?version=1', 'Unknown duration waits for a later durationchange');
    metadata.duration = 154; metadata.fire('durationchange');
    assert.strictEqual(tracks[0].querySelector('[data-track-duration]').textContent, '2:34');
    assert.strictEqual(sourceUrl(metadata), '/recording.mp4');
    assert.strictEqual(metadata.playlistSource.getAttribute('type'), 'video/mp4');
    assert.strictEqual(recordings.length, initialCount + 1, 'Reading the next duration reuses the metadata element');
    tracks[0].querySelector('[data-track-play]').fire('click');
    assert(observer.disconnected, 'Playback disconnects background metadata observation');
    assert.strictEqual(timers.size, 0, 'Playback cancels background metadata timeouts');
    assert.strictEqual(metadata.events.loadedmetadata, undefined, 'Cancelled metadata listeners are removed');
    assert.strictEqual(sourceUrl(metadata), '', 'Playback releases the background recording source');
    const playback = recordings[initialCount + 1];
    playback.duration = NaN; playback.fire('loadedmetadata');
    playback.duration = 186; playback.fire('durationchange');
    assert.strictEqual(tracks[0].querySelector('[data-track-duration]').textContent, '3:06', 'Late playback duration updates the row');
    assert.strictEqual(player.querySelector('[data-duration]').textContent, '3:06');
    metadata.duration = 999; staleMetadata();
    assert.strictEqual(tracks[0].querySelector('[data-track-duration]').textContent, '3:06', 'Cancelled metadata cannot overwrite playback duration');
    observer.callback(tracks.map(target => ({target, isIntersecting: true})));
    assert.strictEqual(recordings.length, initialCount + 2, 'Scrolling never restarts background audio after playback');
    tracks[1].querySelector('[data-track-play]').fire('click');
    assert.strictEqual(recordings.length, initialCount + 2, 'Every track uses the same playback element');
    hiddenPage(); assert(playback.paused);

    // Without playback, a slow or failed duration must not block later tracks.
    initialize(doc, win);
    observer.callback(tracks.slice(0, 2).map(target => ({target, isIntersecting: true})));
    const timedMetadata = recordings[recordings.length - 1];
    timedMetadata.duration = NaN;
    const timeout = timers.values().next().value;
    timeout();
    assert.strictEqual(sourceUrl(timedMetadata), '/recording.mp4', 'Timed-out metadata advances to the next track');
    timedMetadata.fire('error');
    assert.strictEqual(timers.size, 0, 'Failed metadata clears its timeout');
    hiddenPage();

    // Known MP3/M4A types are explicit; unknown extensions keep browser detection.
    delete win.IntersectionObserver;
    initialize(doc, win);
    for (const [url, type] of [['/recording.mp3#fragment', 'audio/mpeg'], ['/recording.m4a', 'audio/mp4'], ['/recording.wav', null]]) {
        tracks[0].attributes['data-audio'] = url;
        tracks[0].querySelector('[data-track-play]').fire('click');
        const media = recordings[recordings.length - 1];
        assert.strictEqual(sourceUrl(media), url);
        assert.strictEqual(media.playlistSource.getAttribute('type'), type, 'Source type updates without stale MP3 hints');
        tracks[1].querySelector('[data-track-play]').fire('click');
    }
    hiddenPage();

    let sectionCallbacks, sectionSelections = [];
    win.VideoMoments = {};
    win.PlaylistMoments = {create(document, dock, guide, callbacks) {
        sectionCallbacks = callbacks;
        return {select(row) { sectionSelections.push(row); }, synchronize() {}};
    }};
    initialize(doc, win);
    tracks[0].querySelector('[data-track-play]').fire('click');
    const sectionAudio = recordings[recordings.length - 1];
    sectionAudio.pause(); sectionCallbacks.seek(28.2, false);
    assert.strictEqual(sectionAudio.currentTime, 28.2); assert(sectionAudio.paused, 'Markers preserve paused playback');
    sectionCallbacks.seek(47, true);
    assert.strictEqual(sectionAudio.currentTime, 47); assert(!sectionAudio.paused, 'Section rows seek and play');
    sectionAudio.duration = NaN; sectionCallbacks.seek(83, true);
    sectionAudio.duration = 154; sectionAudio.fire('loadedmetadata');
    assert.strictEqual(sectionAudio.currentTime, 83, 'Section seek waits for metadata');
    sectionAudio.duration = NaN; sectionCallbacks.seek(84, true);
    doc.fire('show.bs.offcanvas', {target: new Element({'id': 'sections-options'})});
    sectionAudio.duration = 154; sectionAudio.fire('loadedmetadata');
    assert(player.hidden && sectionAudio.paused, 'Late section metadata cannot restart playback after a panel opens');
    assert.strictEqual(sectionAudio.currentTime, 83, 'Closing cancels the queued section seek');

    sectionAudio.duration = NaN; sectionCallbacks.seek(91, true);
    sectionAudio.currentTime = 0; // Loading the next real media source resets its time.
    tracks[1].querySelector('[data-track-play]').fire('click');
    sectionAudio.duration = 154; sectionAudio.currentTime = 0; sectionAudio.fire('loadedmetadata');
    assert.strictEqual(sectionAudio.currentTime, 0, 'Changing track clears pending section seek');
    const previousUpgrades = upgrades;
    sectionCallbacks.seek(47, true);
    assert(sectionAudio.paused); assert.strictEqual(sectionAudio.currentTime, 10);
    assert.strictEqual(upgrades, previousUpgrades + 1, 'Sections obey the preview cutoff');
    assert.deepStrictEqual(sectionSelections, [tracks[0], tracks[1]]);
    delete win.PlaylistMoments; delete win.VideoMoments;

    let volumeChange;
    const deviceVolume = {matches: true, addEventListener(event, callback) { volumeChange = callback; }};
    win.matchMedia = () => deviceVolume;
    delete win.IntersectionObserver;
    initialize(doc, win);
    const volumeControl = player.querySelector('.playlist-player__volume');
    assert(volumeControl.hidden, 'Mobile hides the volume slider and mute control');
    tracks[0].querySelector('[data-track-play]').fire('click');
    const mobileAudio = recordings[recordings.length - 1];
    assert.strictEqual(mobileAudio.volume, 1, 'Mobile uses unattenuated media volume beneath the device setting');
    assert.strictEqual(mobileAudio.muted, false);
    deviceVolume.matches = false; volumeChange();
    assert(!volumeControl.hidden, 'Desktop retains the volume controls');
    assert.strictEqual(mobileAudio.volume, .5);
    player.querySelector('[data-volume]').value = .3;
    player.querySelector('[data-volume]').fire('input');
    player.querySelector('[data-mute]').fire('click');
    assert.strictEqual(mobileAudio.volume, .3); assert(mobileAudio.muted);
    deviceVolume.matches = true; volumeChange();
    assert.strictEqual(mobileAudio.volume, 1); assert(!mobileAudio.muted);
    player.querySelector('[data-volume]').fire('input');
    player.querySelector('[data-mute]').fire('click');
    assert.strictEqual(mobileAudio.volume, 1); assert(!mobileAudio.muted, 'Hidden controls cannot mute mobile playback');
    deviceVolume.matches = false; volumeChange();
    assert.strictEqual(mobileAudio.volume, .3); assert(mobileAudio.muted, 'Returning to desktop restores its volume/mute preferences');
    // Collections have neither reorder permission nor a handle in their markup.
    delete list.attributes['data-url-reorder'];
    tracks.forEach(row => { row.querySelector('[data-track-handle]').events = {}; });
    initialize(doc, win);
    tracks.forEach(row => {
        const handle = row.querySelector('[data-track-handle]');
        assert.strictEqual(handle.events.pointerdown, undefined, 'Read-only lists never bind drag handlers');
        assert.strictEqual(handle.events.keydown, undefined, 'Read-only lists never bind keyboard ordering');
    });
    const originalSelectors = tracks.map(row => row.querySelector);
    tracks.forEach(row => { row.querySelector = function (selector) { return selector === '[data-track-handle]' ? null : Element.prototype.querySelector.call(this, selector); }; });
    const collectionOrder = list.items.slice();
    initialize(doc, win);
    page.querySelector('[data-play-all]').fire('click');
    assert(!recordings[recordings.length - 1].paused, 'Collections without handles still initialize and play');
    assert.deepStrictEqual(list.items, collectionOrder);
    const collectionAudio = recordings[recordings.length - 1];
    doc.fire('show.bs.offcanvas', {target: new Element({'id': 'new-dynamic-panel'})});
    assert(player.hidden && collectionAudio.paused, 'Collection players close for any dynamically added offcanvas');
    page.querySelector('[data-play-all]').fire('click');
    assert(!player.hidden && !collectionAudio.paused, 'Play all can reopen the collection player');
    assert.strictEqual(doc.listenerOptions.play, true, 'Video play is captured because native media events do not bubble');
    doc.fire('play', {target: {tagName: 'AUDIO'}});
    assert(!player.hidden && !collectionAudio.paused, 'Audio playback does not close its own band');
    doc.fire('play', {target: {tagName: 'VIDEO', id: 'loaded-later-video'}});
    assert(player.hidden && collectionAudio.paused, 'Video playback closes the shared player on collection pages too');


    tracks.forEach((row, index) => { row.querySelector = originalSelectors[index]; });
    // Piece pages reuse the band without playlist action buttons or visible rows.
    const piecePage = new Element(), pieceList = new Element(), pieceBand = new Element(), launch = new Element();
    const pieceMain = new Element(); pieceMain.style.marginBottom = '24px';
    piecePage.closest = () => pieceMain;
    piecePage.style.setProperty = function (name, value) { this[name] = value; };
    const pieceTracks = ['full'].map(hand => {
        const row = new Element({'data-hand': hand, 'data-title': 'The Storm', 'data-composer': 'Burgmüller', 'data-audio': '/' + hand + '.mp3', 'data-preview': '10', 'data-artwork': '/art.jpg'});
        row.querySelector = () => null;
        return row;
    });
    pieceList.querySelectorAll = () => pieceTracks;
    const pieceStatus = pieceBand.querySelector('[data-playlist-status]');
    piecePage.children = {'[data-playlist-tracks]': pieceList, '[data-playlist-player]': pieceBand, '[data-playlist-status]': pieceStatus};
    piecePage.querySelector = selector => piecePage.children[selector] || null;
    const pieceDoc = new Element();
    pieceDoc.querySelector = selector => selector === '[data-piece-player-page]' ? piecePage : null;
    pieceDoc.getElementById = id => id === 'launch-audio' ? launch : new Element();
    pieceDoc.createElement = () => new Element();
    pieceDoc.documentElement = new Element();
    pieceDoc.documentElement.style.setProperty = function (name, value) { this[name] = value; };
    let videoPauses = 0;
    pieceDoc.querySelectorAll = () => [{pause() { videoPauses++; }}];
    const pieceWin = {...win};
    const beforePiece = recordings.length;
    initialize(pieceDoc, pieceWin);
    assert(pieceBand.hidden);
    assert.strictEqual(recordings.length, beforePiece, 'Piece recordings remain unloaded before Listen');
    assert.strictEqual(pieceMain.style.marginBottom, '24px', 'Hidden band preserves the original page spacing');
    launch.fire('click');
    const pieceAudio = recordings[beforePiece];
    assert(!pieceBand.hidden); assert(!pieceAudio.paused);
    assert.strictEqual(sourceUrl(pieceAudio), '/full.mp3');
    assert.strictEqual(piecePage.style['--playlist-menu-height'], '0px', 'Piece band has no menu offset');
    assert.strictEqual(pieceDoc.documentElement.style['--piece-player-height'], '80px', 'Score toolbar receives the band height');
    assert.strictEqual(launch.getAttribute('aria-expanded'), 'true');
    assert.strictEqual(videoPauses, 1, 'Listen pauses piece videos');
    pieceDoc.fire('show.bs.offcanvas', {target: new Element({'id': 'save-to-offcanvas'})});
    assert(pieceBand.hidden && pieceAudio.paused, 'Piece players close and pause when an offcanvas opens');
    assert.strictEqual(pieceDoc.documentElement.style['--piece-player-height'], '0px', 'Panel opening releases the score toolbar offset');
    assert.strictEqual(pieceMain.style.marginBottom, '24px');
    assert.strictEqual(launch.getAttribute('aria-expanded'), 'false');
    launch.fire('click');
    assert(!pieceBand.hidden && !pieceAudio.paused, 'Listen reopens the player after panel dismissal');

    pieceBand.querySelector('[data-speed]');
    pieceBand.querySelector('[data-player-toggle]').fire('click'); assert(pieceAudio.paused);
    launch.fire('click'); assert(!pieceAudio.paused);
    pieceAudio.currentTime = 14; pieceAudio.fire('timeupdate');
    assert(pieceAudio.paused); assert.strictEqual(pieceAudio.currentTime, 10);
    assert(pieceStatus.textContent.includes('preview has ended'));
    launch.fire('click'); assert.strictEqual(pieceAudio.currentTime, 0); assert(!pieceAudio.paused);
    pieceBand.querySelector('[data-player-close]').fire('click');
    assert(pieceAudio.paused); assert(pieceBand.hidden);
    assert.strictEqual(pieceDoc.documentElement.style['--piece-player-height'], '0px');
    assert.strictEqual(pieceMain.style.marginBottom, '24px');
    assert.strictEqual(launch.getAttribute('aria-expanded'), 'false');
    launch.fire('click'); assert(!pieceBand.hidden); assert(!pieceAudio.paused);
    pieceWin.PieceAudioPlayer.pause(); assert(pieceAudio.paused, 'Visibility lifecycle pauses the retained recording');
    pieceAudio.reject(new Error('Playback blocked')); await settle();
    assert(pieceStatus.textContent.includes('could not start'));
    pieceTracks.forEach(row => row.setAttribute('data-preview', '0'));
    launch.fire('click');
    pieceAudio.currentTime = 25; pieceAudio.fire('timeupdate');
    assert(!pieceAudio.paused, 'Full-access recordings continue beyond the preview limit');
    pieceAudio.currentTime = 154; pieceAudio.paused = true; pieceAudio.ended = true; pieceAudio.fire('ended');
    assert.strictEqual(sourceUrl(pieceAudio), '/full.mp3', 'Ending a piece retains its main recording');
    launch.fire('click'); assert.strictEqual(pieceAudio.currentTime, 0); assert(!pieceAudio.paused);
    pieceBand.querySelector('[data-player-close]').fire('click');
    assert.strictEqual(pieceDoc.listenerOptions.play, true);
    for (const videoId of ['piece-performance', 'piece-synthesia', 'piece-video-42']) {
        launch.fire('click');
        assert(!pieceBand.hidden && !pieceAudio.paused);
        pieceDoc.fire('play', {target: {tagName: 'VIDEO', id: videoId}});
        assert(pieceBand.hidden && pieceAudio.paused, videoId + ' playback hides and pauses audio');
        assert.strictEqual(pieceDoc.documentElement.style['--piece-player-height'], '0px');
        assert.strictEqual(launch.getAttribute('aria-expanded'), 'false');
    }
    launch.fire('click');
    assert(!pieceBand.hidden && !pieceAudio.paused, 'Listen reopens after video playback');
    pieceBand.querySelector('[data-player-close]').fire('click');
    console.log('Passed: performance/Synthesia/dynamic video dismissal, captured media events and audio exclusion; offcanvas dismissal across folder, collection and piece players; piece Listen/Close, score spacing, main recording, full playback and previews; typed MPGA/MP3/MP4/M4A sources, playlist visibility, device-only mobile volume, shared playback element, delayed metadata, loader cancellation/timeouts, preview boundaries, stale events, speed, loop, mouse/touch drag lifecycle, cancellation, reorder rollback and favorite failures.');
};
