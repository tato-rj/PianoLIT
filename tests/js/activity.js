const assert = require('assert');
const install = require('../../resources/js/components/activity');

module.exports = async function () {
    function fixture(storage = new Map(), options = {}) {
        let time = 0, axiosInterceptor;
        const handlers = {}, requests = [], beacons = [], intervals = [];
        const doc = {
            visibilityState: 'visible', querySelectorAll: () => [],
            addEventListener(name, callback) { handlers['doc:' + name] = callback; }
        };
        const win = {
            document: doc, performance: {now: () => time}, crypto: require('crypto').webcrypto || {getRandomValues: bytes => require('crypto').randomFillSync(bytes)},
            URL, URLSearchParams, location: new URL('https://my.pianolit.test/explore'),
            axios: {interceptors: {request: {use(callback) { axiosInterceptor = callback; }}}},
            sessionStorage: {getItem: key => storage.get(key), setItem: (key, value) => storage.set(key, value)},
            Blob: class { constructor(parts) { this.text = parts.join(''); } },
            navigator: {sendBeacon(url, blob) { beacons.push(JSON.parse(blob.text)); return true; }},
            fetch(url, options) { return new Promise((resolve, reject) => { requests.push({url, options, data: JSON.parse(options.body), resolve, reject}); }); },
            addEventListener(name, callback) { handlers[name] = callback; },
            setInterval(callback, delay) { intervals.push({callback, delay}); },
            setTimeout(callback) { callback(); }
        };
        install(win, Object.assign({endpoint: '/activity/events', pageToken: 'signed-page', csrfToken: 'csrf', pieceId: 42}, options));
        return {win, handlers, requests, beacons, intervals, storage, axios: request => axiosInterceptor(request), at: value => { time = value; }, queued: () => JSON.parse(storage.get('pianolit.activity.outbox.v1')).flatMap(batch => batch.events)};
    }
    async function settle() { for (let i = 0; i < 5; i++) await Promise.resolve(); }
    const f = fixture();
    assert.strictEqual(f.requests[0].data.events[0].type, 'page_ready');
    assert.strictEqual(f.requests[0].options.credentials, 'same-origin');
    assert.strictEqual(f.requests[0].options.headers['X-CSRF-TOKEN'], 'csrf');
    assert.strictEqual(f.axios({url: '/favorite', headers: {}}).headers['X-Activity-Page'], 'signed-page');
    assert.strictEqual(f.axios({url: '/favorite', baseURL: 'https://external.example', headers: {}}).headers['X-Activity-Page'], undefined, 'Page tokens never accompany third-party Axios requests');
    f.requests[0].resolve({ok: true}); await settle();
    f.at(7000);
    const link = {id: '', tagName: 'A', textContent: 'Early beginner', classList: {contains: () => false}, closest: () => null,
        hasAttribute: () => false, getAttribute: key => key === 'href' ? '/explore?level=early-beginner&token=private' : null};
    f.handlers['doc:click']({target: {closest: () => link}});
    const click = f.queued()[0];
    assert.strictEqual(click.elapsed_ms, 7000);
    assert.strictEqual(click.metadata.label, 'Early beginner');
    assert.strictEqual(click.metadata.target, 'https://my.pianolit.test/explore?level=early-beginner');
    f.handlers['doc:click']({target: {closest: () => Object.assign({}, link, {tagName: 'SPAN', classList: {contains: name => name === 'tag'}, getAttribute: name => name === 'data-name' ? 'calm' : null})}});
    assert.strictEqual(f.queued()[1].type, 'filter_change', 'Clickable tag spans record their selection');
    assert.strictEqual(f.queued()[1].metadata.value, 'calm');
    f.handlers['doc:click']({target: {closest: () => Object.assign({}, link, {tagName: 'BUTTON', getAttribute: name => name === 'data-select-level' ? '123' : null})}});
    assert.strictEqual(f.queued()[2].metadata.piece_id, 123, 'Match selections identify the actual selected piece');
    f.handlers['doc:click']({target: {closest: () => Object.assign({}, link, {getAttribute: name => name === 'href' ? '/password/reset/private-token?email=secret@example.test' : null})}});
    assert.strictEqual(f.queued()[3].metadata.target, 'https://my.pianolit.test/password/reset/[redacted]');
    const cards = fixture();
    const pieceLink = attributes => Object.assign({}, link, {
        textContent: 'A Sad Song Y. Levitin Early beginner Video Audio Score Synthesia',
        getAttribute: name => Object.prototype.hasOwnProperty.call(attributes, name) ? attributes[name] : null
    });
    cards.handlers['doc:click']({target: {closest: () => pieceLink({'href': '/pieces/178?token=private#score', 'data-activity-label': 'A Sad Song by Y. Levitin'})}});
    let selection = cards.queued().filter(event => event.type === 'click').pop().metadata;
    assert.strictEqual(selection.label, 'A Sad Song by Y. Levitin', 'Card badges are not part of the navigation label');
    assert.strictEqual(selection.piece_id, 178, 'The destination takes precedence over the piece on the source page');
    assert.strictEqual(selection.target, 'https://my.pianolit.test/pieces/178');
    cards.handlers['doc:click']({target: {closest: () => pieceLink({'data-url': '/pieces/179'})}});
    selection = cards.queued().filter(event => event.type === 'click').pop().metadata;
    assert.strictEqual(selection.label, 'a piece', 'Unlabeled piece cards do not fall back to scraping their contents');
    assert.strictEqual(selection.piece_id, 179);
    cards.win.location = new URL('https://my.pianolit.test/pieces/42');
    cards.handlers['doc:click']({target: {closest: () => Object.assign({}, link, {textContent: 'Score', getAttribute: name => name === 'href' ? '#score' : null})}});
    assert.strictEqual(cards.queued().filter(event => event.type === 'click').pop().metadata.label, 'Score', 'Same-page tabs retain their control labels');
    cards.handlers['doc:click']({target: {closest: () => Object.assign({}, link, {tagName: 'BUTTON', textContent: 'Favorite', getAttribute: () => null})}});
    assert.strictEqual(cards.queued().filter(event => event.type === 'click').pop().metadata.target, '', 'Buttons without a destination do not invent a piece link');
    cards.handlers['doc:click']({target: {closest: () => Object.assign({}, link, {textContent: 'External link', getAttribute: name => name === 'href' ? 'https://external.example/pieces/178' : null})}});
    assert.strictEqual(cards.queued().filter(event => event.type === 'click').pop().metadata.label, 'External link', 'An external path does not identify a PianoLIT piece');
    const mediaHandlers = {};
    const media = {tagName: 'VIDEO', id: 'performance', currentTime: 0, duration: 90, playbackRate: 1, paused: false,
        addEventListener(name, callback) { mediaHandlers[name] = callback; }};
    f.win.PianoActivity.media(media, {media_kind: 'video', piece_id: 42});
    const playing = {type: 'playing', target: media};
    mediaHandlers.playing(playing); f.handlers['doc:playing'](playing);
    assert.strictEqual(f.queued().filter(event => event.type === 'media_play').length, 1, 'Capturing and direct media listeners do not double-record');
    f.at(19000); media.currentTime = 12; media.paused = true;
    mediaHandlers.pause({type: 'pause', target: media});
    const pause = f.queued().find(event => event.type === 'media_pause');
    assert.strictEqual(pause.metadata.position_seconds, 12);
    assert.strictEqual(pause.metadata.played_seconds, 12);
    assert.strictEqual(pause.metadata.piece_id, 42);
    assert.strictEqual(pause.elapsed_ms, 19000);
    const swapped = fixture();
    const swappedHandlers = {};
    const sharedAudio = {tagName: 'AUDIO', currentTime: 0, duration: 90, playbackRate: 1, paused: false,
        addEventListener(name, callback) { swappedHandlers[name] = callback; }};
    swapped.win.PianoActivity.media(sharedAudio, {media_kind: 'audio', media_id: 'first', piece_id: 1});
    swappedHandlers.playing({type: 'playing'});
    swapped.at(4000); sharedAudio.currentTime = 4;
    swapped.win.PianoActivity.media(sharedAudio, {media_kind: 'audio', media_id: 'second', piece_id: 2});
    sharedAudio.currentTime = 0; swappedHandlers.pause({type: 'pause'});
    assert.strictEqual(swapped.queued().filter(event => event.type === 'media_pause').length, 1);
    assert.strictEqual(swapped.queued().find(event => event.type === 'media_pause').metadata.piece_id, 1, 'A queued native pause must retain the old piece identity');
    assert.strictEqual(swapped.queued().find(event => event.type === 'media_pause').metadata.played_seconds, 4);
    swappedHandlers.playing({type: 'playing'}); swapped.at(9000); sharedAudio.currentTime = 5;
    swapped.win.PianoActivity.stopMedia(sharedAudio); sharedAudio.currentTime = 0; swappedHandlers.pause({type: 'pause'});
    const stopped = swapped.queued().filter(event => event.type === 'media_pause');
    assert.strictEqual(stopped.length, 2, 'An explicit stop and its queued native pause produce one event');
    assert.strictEqual(stopped[1].metadata.piece_id, 2);
    assert.strictEqual(stopped[1].metadata.position_seconds, 5, 'Resetting the player does not erase its stopping position');
    f.handlers.pagehide();
    assert(f.beacons[0].events.some(event => event.type === 'page_leave'));
    assert(f.queued().some(event => event.type === 'page_leave'), 'Beacon delivery is retained until acknowledged');
    f.win.PianoActivity.flush();
    const batch = f.requests[1]; batch.reject(new Error('Offline')); await settle();
    assert(f.queued().some(event => event.id === click.id));
    f.handlers.online();
    assert.strictEqual(f.requests[2].data.events[0].id, click.id, 'Retry preserves IDs');
    f.requests[2].resolve({ok: true}); await settle();
    assert.deepStrictEqual(f.queued(), []);
    const pending = fixture(); pending.at(1000); pending.handlers.pagehide();
    const next = fixture(pending.storage);
    assert.strictEqual(next.requests[0].data.page_token, 'signed-page');
    assert.strictEqual(next.requests[0].data.events[0].id, pending.requests[0].data.events[0].id, 'Unacknowledged history survives navigation');
    next.requests[0].resolve({ok: false, status: 403}); await settle();
    assert(!next.queued().some(event => event.id === pending.requests[0].data.events[0].id), 'An expired/account-mismatched token is discarded');
    const hidden = fixture(); hidden.at(5000); hidden.win.document.visibilityState = 'hidden'; hidden.handlers['doc:visibilitychange']();
    hidden.at(10000); hidden.win.document.visibilityState = 'visible'; hidden.handlers['doc:visibilitychange']();
    hidden.at(13000); hidden.handlers.pagehide();
    assert.strictEqual(hidden.queued().find(event => event.type === 'page_leave').metadata.visible_ms, 8000, 'Hidden time is excluded from foreground duration');
    const noConfig = {};
    install(noConfig, null); assert.strictEqual(noConfig.PianoActivity, undefined);
    const tour = fixture(), modal = {id: 'match-tour-modal', querySelector: () => ({getAttribute: () => '321'})};
    const tourButton = attributes => ({id: '', tagName: 'BUTTON', textContent: 'I like this one', classList: {contains: () => false},
        hasAttribute: name => Object.prototype.hasOwnProperty.call(attributes, name), getAttribute: name => Object.prototype.hasOwnProperty.call(attributes, name) ? attributes[name] : null,
        closest: selector => selector === '.match-tour-modal, #match-tour' ? modal : null});
    tour.handlers['doc:shown.bs.modal']({target: modal});
    tour.handlers['doc:click']({target: {closest: () => tourButton({'data-listen-confirm': ''})}});
    let confirmed = tour.queued().find(event => event.metadata.control === 'match_preference_confirm');
    assert.strictEqual(confirmed.metadata.section, 'find-your-match');
    assert.strictEqual(confirmed.metadata.piece_id, 321, 'The confirmation records the selected piece rather than the underlying page piece');
    tour.handlers['doc:click']({target: {closest: () => tourButton({'data-choice': '321'})}});
    assert.strictEqual(tour.queued().filter(event => event.type === 'click').pop().metadata.piece_id, 321);
    modal.querySelector = () => null;
    tour.handlers['doc:click']({target: {closest: () => tourButton({'data-listen-confirm': ''})}});
    confirmed = tour.queued().filter(event => event.metadata.control === 'match_preference_confirm').pop();
    assert.strictEqual(confirmed.metadata.piece_id, undefined, 'Missing selection cannot borrow the underlying piece page identity');
    tour.handlers['doc:hidden.bs.modal']({target: modal});
    assert.strictEqual(tour.queued().filter(event => event.type === 'section_open' && event.metadata.section === 'find-your-match').length, 1);
    assert.strictEqual(tour.queued().filter(event => event.type === 'section_close' && event.metadata.section === 'find-your-match').length, 1);
    tour.handlers['doc:shown.bs.modal']({target: {id: 'another-modal'}});
    assert.strictEqual(tour.queued().filter(event => event.type === 'section_open').length, 1, 'Other modals do not establish a Find your match context');
    const watched = fixture(), watchedHandlers = {};
    const clip = {tagName: 'VIDEO', currentTime: 0, duration: 100, playbackRate: 1, paused: false, seeking: false,
        addEventListener(name, callback) { watchedHandlers[name] = callback; }};
    watched.win.PianoActivity.media(clip, {media_kind: 'video', piece_id: 42});
    watchedHandlers.playing({type: 'playing'});
    watched.at(10000); clip.currentTime = 10; watchedHandlers.timeupdate({type: 'timeupdate'});
    clip.seeking = true; clip.currentTime = 80; watchedHandlers.seeking({type: 'seeking'});
    watched.at(11000); clip.seeking = false; watchedHandlers.seeked({type: 'seeked'});
    watched.at(21000); clip.currentTime = 90; watchedHandlers.timeupdate({type: 'timeupdate'});
    watchedHandlers.pause({type: 'pause'});
    let coverage = watched.queued().filter(event => event.type === 'media_pause').pop().metadata;
    assert.strictEqual(coverage.unique_watched_percent, 20, 'Skipping to 80% then playing ten seconds counts only the twenty seconds actually played');
    assert.strictEqual(coverage.unique_watched_seconds, 20);
    assert(coverage.playback_id, 'Compact summaries retain a stable playback identity');
    watched.at(22000); clip.currentTime = 0; clip.seeking = true; watchedHandlers.seeking({type: 'seeking'});
    clip.seeking = false; watchedHandlers.seeked({type: 'seeked'});
    watched.at(32000); clip.currentTime = 10; watchedHandlers.timeupdate({type: 'timeupdate'});
    watchedHandlers.pause({type: 'pause'});
    coverage = watched.queued().filter(event => event.type === 'media_pause').pop().metadata;
    assert.strictEqual(coverage.unique_watched_percent, 20, 'Replaying the same content does not increase unique coverage');
    assert.strictEqual(watched.queued().filter(event => event.type === 'media_play').length, 1, 'Pause/resume and seeking do not create another playback start');
    assert.strictEqual(watched.queued().filter(event => event.type === 'media_progress').length, 0, 'Sampling timeupdate adds no per-second database events');
    const buffered = fixture(), bufferedHandlers = {};
    const video = {tagName: 'VIDEO', id: 'piece-performance', currentTime: 0, duration: 88.468027, playbackRate: 1, paused: false,
        addEventListener(name, callback) { bufferedHandlers[name] = callback; }};
    buffered.win.PianoActivity.media(video);
    bufferedHandlers.playing({type: 'playing'});
    buffered.at(517); video.currentTime = 0.368933; bufferedHandlers.waiting({type: 'waiting'});
    buffered.at(1517); bufferedHandlers.playing({type: 'playing'});
    buffered.at(2517); video.currentTime = 1.368933; bufferedHandlers.pause({type: 'pause'});
    let starts = buffered.queued().filter(event => event.type === 'media_play');
    assert.strictEqual(starts.length, 1, 'Buffering recovery does not record the same start twice');
    let observation = buffered.queued().filter(event => event.type === 'media_pause').pop().metadata;
    assert.strictEqual(observation.played_seconds, 1.517, 'Buffering time is excluded while playback across recovery is retained');
    assert.strictEqual(observation.playback_id, starts[0].metadata.playback_id);
    bufferedHandlers.playing({type: 'playing'});
    buffered.at(3517); video.currentTime = 2.368933;
    assert.strictEqual(buffered.queued().filter(event => event.type === 'media_play').length, 1, 'Explicit pause/resume keeps the existing start');
    bufferedHandlers.ended({type: 'ended'});
    assert.strictEqual(buffered.queued().filter(event => event.type === 'media_end').pop().metadata.played_seconds, 2.517);
    video.currentTime = 0; bufferedHandlers.playing({type: 'playing'});
    starts = buffered.queued().filter(event => event.type === 'media_play');
    assert.strictEqual(starts.length, 2, 'A new viewing after the video ends has its own start');
    assert.notStrictEqual(starts[1].metadata.playback_id, starts[0].metadata.playback_id);
    assert.strictEqual(starts[1].metadata.played_seconds, 0, 'A completed replay resets cumulative playing time');
    const restored = fixture(), restoreMediaHandlers = {};
    const restoreMedia = {tagName: 'VIDEO', currentTime: 0, duration: 90, playbackRate: 1, paused: false,
        addEventListener(name, callback) { restoreMediaHandlers[name] = callback; }};
    restored.win.PianoActivity.media(restoreMedia);
    restoreMediaHandlers.playing({type: 'playing'});
    restoreMedia.currentTime = 5;
    restored.at(5000); restored.handlers.pagehide();
    restored.at(65000); restored.handlers.pageshow({persisted: true});
    restoreMedia.currentTime = 8;
    restored.at(68000); restored.handlers.pagehide();
    assert.strictEqual(restored.queued().filter(event => event.type === 'page_leave').pop().metadata.visible_ms, 8000, 'Time away in the back-forward cache does not count as visible');
    assert.strictEqual(restored.queued().filter(event => event.type === 'media_progress').pop().metadata.played_seconds, 8, 'Frozen back-forward cache time does not count as playback');
    const gated = fixture(new Map(), {visitId: 'first-visit', deferred: true, minimumVisitMs: 5000});
    assert.strictEqual(gated.requests.length, 0, 'Page readiness stays in the browser before qualification');
    gated.at(2000); gated.handlers.pagehide();
    assert.strictEqual(gated.beacons.length, 0, 'A two-second visit does not send an analytics batch');
    const continued = fixture(gated.storage, {visitId: 'first-visit', pageToken: 'second-page', deferred: true, minimumVisitMs: 5000});
    assert.strictEqual(continued.requests.length, 0);
    continued.at(3000); continued.win.PianoActivity.record('media_pause', {media_kind: 'audio', played_seconds: 3});
    continued.intervals.find(timer => timer.delay === 1000).callback();
    assert.strictEqual(continued.requests.length, 1, 'Two seconds on the first page plus three on the next qualifies the visit');
    const qualifying = continued.requests[0].data;
    assert.strictEqual(qualifying.page_token, 'signed-page', 'The first page is retained in the chronological story');
    assert.strictEqual(qualifying.qualification.length, 2);
    assert.strictEqual(qualifying.qualification.reduce((sum, proof) => sum + proof.event.metadata.visible_ms, 0), 5000);
    const originalId = qualifying.events[0].id;
    continued.requests[0].resolve({ok: true, status: 202}); await settle();
    assert(continued.queued().some(event => event.id === originalId), 'A pending response does not acknowledge or erase the browser buffer');
    continued.handlers.online();
    assert.strictEqual(continued.requests[1].data.events[0].id, originalId);
    continued.requests[1].resolve({ok: true, status: 204}); await settle();
    continued.win.PianoActivity.flush();
    assert.strictEqual(continued.requests[2].data.page_token, 'second-page');
    assert.strictEqual(continued.requests[2].data.qualification, undefined, 'Subsequent batches reuse the qualified visit without extra proofs');
    const background = fixture(new Map(), {visitId: 'hidden-visit', deferred: true, minimumVisitMs: 5000});
    background.at(2000); background.win.document.visibilityState = 'hidden'; background.handlers['doc:visibilitychange']();
    background.at(62000); background.intervals.find(timer => timer.delay === 1000).callback();
    assert.strictEqual(background.requests.length, 0, 'A minute in a hidden tab does not qualify a two-second viewing');
    background.win.document.visibilityState = 'visible'; background.handlers['doc:visibilitychange']();
    background.at(65000); background.intervals.find(timer => timer.delay === 1000).callback();
    assert.strictEqual(background.requests.length, 1);
    const abandoned = fixture(new Map(), {visitId: 'abandoned', deferred: true, minimumVisitMs: 5000});
    abandoned.at(1000); abandoned.handlers.pagehide();
    const fresh = fixture(abandoned.storage, {visitId: 'new-visit', pageToken: 'fresh-page', deferred: true, minimumVisitMs: 5000});
    fresh.at(5000); fresh.intervals.find(timer => timer.delay === 1000).callback();
    assert.strictEqual(fresh.requests[0].data.page_token, 'fresh-page', 'Unqualified old visits cannot block later valid activity');
    assert(!fresh.queued().some(event => event.type === 'page_leave'), 'Old one-second browsing is discarded when the visit changes');
};
