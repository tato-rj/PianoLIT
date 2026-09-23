const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

module.exports = async function () {
    const window = {};
    vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../resources/js/views/piece-access.js'), 'utf8'), {window});
    const handlers = {};
    let prompts = 0;
    let exitedFullscreen = 0;
    const document = {
        fullscreenElement: {},
        exitFullscreen() { exitedFullscreen++; return Promise.resolve(); },
        addEventListener(event, callback, capture) { assert.strictEqual(capture, true); handlers[event] = callback; }
    };
    window.PieceAccess.installPreviewGuards(document, () => prompts++);
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

    const status = {hidden: false};
    const canvases = [];
    let rendered = 0;
    let cleaned = 0;
    const container = {
        getAttribute: () => '/preview.pdf',
        querySelector: selector => selector.includes('status') ? status : {appendChild: canvas => canvases.push(canvas)},
        ownerDocument: {createElement: () => ({getContext: () => ({})})}
    };
    const pdfjs = {getDocument: () => ({promise: Promise.resolve({
        numPages: 4,
        async getPage(number) {
            assert.strictEqual(number, 1, 'Only the first score page is requested');
            return {
                getViewport: () => ({width: 600, height: 800}),
                render: () => ({promise: Promise.resolve().then(() => rendered++)}),
                cleanup: () => cleaned++
            };
        }
    })})};
    await window.PieceAccess.renderScorePreview(container, pdfjs);
    assert.strictEqual(canvases.length, 1, 'A multi-page score produces only one blurred preview page');
    assert.strictEqual(rendered, 1);
    assert.strictEqual(cleaned, 1);
    assert.strictEqual(status.hidden, true);
    status.hidden = false;
    await window.PieceAccess.renderScorePreview(container, {getDocument: () => ({promise: Promise.reject(new Error('Missing PDF'))})});
    assert(status.textContent.includes('could not be loaded'));
    console.log('Passed: media preview cutoff, seeking/replay, subscriber playback, first-page score preview.');
};
