const assert = require('assert');
const install = require('../../resources/js/components/activity');

module.exports = async function () {
    function fixture(storage = new Map()) {
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
        install(win, {endpoint: '/activity/events', pageToken: 'signed-page', csrfToken: 'csrf', pieceId: 42});
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
};
