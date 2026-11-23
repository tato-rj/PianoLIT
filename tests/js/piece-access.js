const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

module.exports = async function () {
    const window = {};
    vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../resources/js/views/piece-access.js'), 'utf8'), {window});
    const handlers = {};
    let prompts = 0;
    const promptKinds = [];
    let exitedFullscreen = 0;
    const document = {
        fullscreenElement: {},
        exitFullscreen() { exitedFullscreen++; return Promise.resolve(); },
        addEventListener(event, callback, capture) { assert.strictEqual(capture, true); handlers[event] = callback; }
    };
    window.PieceAccess.installPreviewGuards(document, kind => { prompts++; promptKinds.push(kind); });
    const media = (tagName, limit) => ({
        tagName, currentTime: 0, paused: false,
        getAttribute() { return limit; },
        pause() { this.paused = true; }
    });
    const video = media('VIDEO', '10');
    video.currentTime = 9.9;
    handlers.timeupdate({type: 'timeupdate', target: video});
    assert.strictEqual(video.paused, false);
    video.currentTime = 10.1;
    handlers.timeupdate({type: 'timeupdate', target: video});
    assert.strictEqual(video.paused, true);
    assert.strictEqual(video.currentTime, 10);
    assert.strictEqual(prompts, 1);
    assert.strictEqual(promptKinds[0], 'video', 'Video limits open the watching prompt');
    handlers.seeked({type: 'seeked', target: video});
    handlers.timeupdate({type: 'timeupdate', target: video});
    assert.strictEqual(prompts, 1, 'Cutoff events must not repeatedly open the modal');
    video.currentTime = 0;
    video.paused = false;
    handlers.play({type: 'play', target: video});
    assert.strictEqual(video.paused, true, 'Replay cannot resume a completed preview');

    // Newly inserted audio uses the same capture listener; seeking cannot skip the limit.
    const audio = media('AUDIO', '10');
    audio.currentTime = 45;
    handlers.seeking({type: 'seeking', target: audio});
    assert.strictEqual(audio.currentTime, 10);
    assert.strictEqual(audio.paused, true);
    assert.strictEqual(promptKinds[promptKinds.length - 1], 'audio', 'AJAX audio limits open the listening prompt');
    assert(exitedFullscreen > 0);
    const subscriber = media('VIDEO', null);
    subscriber.currentTime = 100;
    handlers.timeupdate({type: 'timeupdate', target: subscriber});
    handlers.ended({type: 'ended', target: subscriber});
    assert.strictEqual(subscriber.paused, false, 'Full-access media is untouched');
    const shortClip = media('VIDEO', '10');
    shortClip.currentTime = 4;
    handlers.ended({type: 'ended', target: shortClip});
    assert.strictEqual(shortClip.paused, true);

    const blocked = media('VIDEO', '0');
    const beforeBlocked = prompts;
    ['timeupdate', 'seeking', 'seeked', 'ended'].forEach(type => handlers[type]({type, target: blocked}));
    assert.strictEqual(prompts, beforeBlocked, 'Zero-second gates never open signup during initial loading');
    assert.strictEqual(blocked.paused, false);
    handlers.play({type: 'play', target: blocked});
    assert.strictEqual(blocked.paused, true, 'Guest regular videos stop as soon as playback is attempted');
    assert.strictEqual(blocked.currentTime, 0);
    assert.strictEqual(prompts, beforeBlocked + 1);
    ['playing', 'timeupdate', 'seeked', 'ended'].forEach(type => handlers[type]({type, target: blocked}));
    assert.strictEqual(prompts, beforeBlocked + 1, 'Queued stop events do not reopen signup');
    blocked.currentTime = 35;
    handlers.seeking({type: 'seeking', target: blocked});
    assert.strictEqual(blocked.currentTime, 0, 'Seeking cannot bypass an immediate gate');
    handlers.play({type: 'play', target: blocked});
    assert.strictEqual(prompts, beforeBlocked + 2, 'Another explicit play attempt opens signup again');
    const inserted = media('VIDEO', '0');
    inserted.currentTime = 40;
    handlers.seeking({type: 'seeking', target: inserted});
    assert.strictEqual(inserted.currentTime, 0, 'AJAX-loaded blocked videos use the same gate');
    assert.strictEqual(promptKinds[promptKinds.length - 1], 'video');

    const blockedAudio = media('AUDIO', '0');
    const beforeAudio = prompts;
    handlers.timeupdate({type: 'timeupdate', target: blockedAudio});
    assert.strictEqual(prompts, beforeAudio, 'Native audio does not prompt on initial loading');
    handlers.play({type: 'play', target: blockedAudio});
    assert(blockedAudio.paused); assert.strictEqual(blockedAudio.currentTime, 0);
    assert.strictEqual(promptKinds[promptKinds.length - 1], 'audio');
    blockedAudio.currentTime = 50;
    handlers.seeking({type: 'seeking', target: blockedAudio});
    assert.strictEqual(blockedAudio.currentTime, 0, 'Guest native audio cannot seek past zero');
    const fullAudio = media('AUDIO', null);
    fullAudio.currentTime = 100;
    handlers.play({type: 'play', target: fullAudio});
    handlers.timeupdate({type: 'timeupdate', target: fullAudio});
    assert.strictEqual(fullAudio.paused, false);

    const status = {hidden: false};
    const pageLabel = {};
    const zoomLabel = {};
    let mobile = false;
    window.matchMedia = () => ({matches: mobile});
    const image = {complete: true, naturalWidth: 48};
    const container = {querySelector: selector => ({'.score-preview-status': status,
        '.score-preview-pages img': image, '[data-page-label]': pageLabel, '[data-zoom-label]': zoomLabel})[selector]};
    window.PieceAccess.renderScorePreview(container);
    assert.strictEqual(status.textContent, 'Preview of the first score page.');
    assert.strictEqual(pageLabel.textContent, 'Page 1');
    assert.strictEqual(zoomLabel.textContent, '75%');
    mobile = true;
    image.complete = false;
    const events = {};
    image.addEventListener = (name, callback) => { events[name] = callback; };
    window.PieceAccess.renderScorePreview(container);
    events.load();
    assert.strictEqual(zoomLabel.textContent, '100%');
    events.error();
    assert(status.textContent.includes('could not be loaded'));
    console.log('Passed: media preview cutoff, seeking/replay, subscriber playback, first-page score preview.');
};
