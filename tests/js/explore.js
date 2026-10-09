const assert = require('assert');
const install = require('../../resources/js/views/explore');

module.exports = async function () {
    function element(text = '') {
        return {hidden: false, textContent: text, attrs: {}, events: {},
            setAttribute(name, value) { this.attrs[name] = value; },
            addEventListener(name, handler) { this.events[name] = handler; }};
    }
    function row(preview) {
        const nodes = {};
        ['toggle', 'play', 'pause', 'idle', 'identity', 'go', 'status', 'title'].forEach(name => { nodes['[data-example-' + name + ']'] = element(name === 'title' ? 'Example title' : ''); });
        return {nodes, querySelector(selector) { return nodes[selector]; }, getAttribute(name) { return name === 'data-preview' ? preview : '/example.mp3'; }};
    }
    const first = row('10'), second = row('0');
    const section = element(); section.open = true; section.contains = item => item === first;
    const audios = [], callbacks = new Map(); let timerId = 0;
    function createAudio() {
        const media = {events: {}, currentTime: 0, playbackRate: 1, paused: true,
            addEventListener(name, handler) { this.events[name] = handler; },
            emit(name) { if (this.events[name]) this.events[name](); },
            pause() { this.paused = true; this.emit('pause'); },
            play() { return new Promise((resolve, reject) => { this.resolve = resolve; this.reject = reject; }); },
            playing() { this.paused = false; this.emit('playing'); this.resolve(); }};
        audios.push(media); return media;
    }
    const player = install({querySelectorAll(selector) { return selector === '[data-explore-example]' ? [first, second] : [section]; }}, createAudio,
        {setTimeout(callback) { callbacks.set(++timerId, callback); return timerId; }, clearTimeout(id) { callbacks.delete(id); }});
    const click = item => item.nodes['[data-example-toggle]'].events.click();
    const node = (item, name) => item.nodes['[data-example-' + name + ']'];
    click(first);
    assert.equal(node(first, 'toggle').attrs['aria-busy'], 'true');
    audios[0].playing();
    assert.equal(node(first, 'identity').hidden, false);
    assert.equal(node(first, 'go').hidden, false);
    assert.equal(node(first, 'pause').hidden, false);
    audios[0].currentTime = 4;
    click(first);
    assert.equal(audios[0].currentTime, 0, 'Pause resets to the beginning');
    assert.equal(node(first, 'identity').hidden, true);
    click(first); audios[1].playing();
    assert.equal(audios[1].currentTime, 0);
    click(second); audios[2].playing();
    assert.equal(audios[1].paused, true, 'Only one example may play');
    assert.equal(node(first, 'identity').hidden, true);
    audios[2].currentTime = 11; audios[2].emit('timeupdate');
    assert.equal(audios[2].paused, false, 'Full-access examples are not truncated');
    click(first); audios[3].playing();
    audios[3].currentTime = 10; audios[3].emit('timeupdate');
    assert.equal(audios[3].paused, true);
    assert.equal(audios[3].currentTime, 0);
    assert.equal(node(first, 'status').textContent, 'Preview ended.');
    click(first); audios[4].playing();
    section.open = false; section.events.toggle();
    assert.equal(audios[4].paused, true, 'Closing a card stops its audio');
    click(first); click(second);
    audios[5].reject(new Error('Interrupted'));
    await Promise.resolve();
    assert.equal(node(second, 'toggle').attrs['aria-busy'], 'true', 'Stale failures cannot reset a newer request');
    audios[6].playing();
    audios[5].playing();
    assert.equal(audios[5].paused, true, 'Stale playback cannot overlap the current example');
    assert.equal(audios[6].paused, false);
    audios[6].emit('ended');
    assert.equal(node(second, 'idle').hidden, false);
    click(first); audios[7].reject(new Error('Unavailable'));
    await Promise.resolve();
    assert.equal(node(first, 'toggle').attrs['aria-busy'], 'false');
    assert.equal(node(first, 'status').hidden, false);
    click(first); audios[8].playing(); player.stop();
    assert.equal(audios[8].paused, true);
    assert.equal(callbacks.size, 0);
    console.log('Explore inline examples: playback, reset, isolation, preview limits and failures passed.');
};
