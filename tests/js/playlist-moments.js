const assert = require('assert');
const fs = require('fs');
const vm = require('vm');
const sections = require('../../resources/js/views/playlist-moments')();

module.exports = function () {
    const root = {};
    vm.runInNewContext(fs.readFileSync('resources/js/views/video-moments.js', 'utf8'), {window: root});
    const guide = root.VideoMoments;
    let doc;
    function node(attributes = {}) {
        const classes = new Set();
        return {
            attributes, children: [], selectors: {}, events: {}, style: {}, hidden: false,
            getAttribute(name) { return this.attributes[name] || null; },
            setAttribute(name, value) { this.attributes[name] = value; },
            removeAttribute(name) { delete this.attributes[name]; },
            querySelector(selector) { return this.selectors[selector] || (this.selectors[selector] = node()); },
            appendChild(child) { this.children.push(child); },
            get firstChild() { return this.children[0]; },
            removeChild(child) { this.children.splice(this.children.indexOf(child), 1); },
            contains(child) { return child && (this === child || [...this.children, ...Object.values(this.selectors)].some(item => item.contains(child))); },
            addEventListener(event, callback) { this.events[event] = callback; },
            fire(event, extra = {}) { this.events[event]({preventDefault() {}, stopPropagation() {}, ...extra}); },
            focus() { doc.activeElement = this; },
            classList: {toggle(name, value) { value ? classes.add(name) : classes.delete(name); }, contains(name) { return classes.has(name); }}
        };
    }
    doc = node(); doc.createElement = () => node();
    const player = node(), panel = player.querySelector('[data-sections-panel]');
    const list = panel.querySelector('[data-sections-list]'), markers = player.querySelector('[data-section-markers]');
    const toggle = player.querySelector('[data-sections-toggle]');
    player.querySelector('[data-section-template]').content = {firstElementChild: {cloneNode: () => node()}};
    player.querySelector('[data-seek]').max = 90;
    const requests = []; let layouts = 0;
    const view = sections.create(doc, player, guide, {seek(seconds, autoplay) { requests.push({seconds, autoplay}); }, layout() { layouts++; }});
    const moments = [
        {id: 1, start_time: 0, end_time: 20, title: '<img src=x> Theme A', comment: '<script>plain text</script>'},
        {id: 2, start_time: 28.2, end_time: 35, title: 'Theme B', comment: 'Second theme'},
        {id: 3, start_time: 47, end_time: null, title: 'Theme C', comment: ''},
        {id: 4, start_time: 50, end_time: null, title: 'Return', comment: 'Opening returns'}
    ];
    view.select(node({'data-audio-moments': JSON.stringify(moments)}));
    assert(!toggle.hidden); assert(panel.hidden); assert.strictEqual(list.children.length, 4);
    assert.strictEqual(list.children[0].querySelector('[data-section-title]').textContent, moments[0].title, 'Titles are plain text');
    const media = {duration: 90, currentTime: 0};
    view.synchronize(media);
    assert.strictEqual(markers.children.length, 4); assert.strictEqual(markers.children[0].style.left, '0%');
    markers.children[1].fire('click'); assert.deepStrictEqual(requests.pop(), {seconds: 28.2, autoplay: false});
    list.children[2].querySelector('[data-section-seek]').fire('click'); assert.deepStrictEqual(requests.pop(), {seconds: 47, autoplay: true});
    toggle.fire('click'); assert(!panel.hidden); assert.strictEqual(toggle.getAttribute('aria-expanded'), 'true');
    assert.strictEqual(list.children[0].getAttribute('aria-current'), 'true');
    media.currentTime = 28.2; view.synchronize(media);
    assert.strictEqual(list.children[1].getAttribute('aria-current'), 'true');
    media.currentTime = 40; view.synchronize(media);
    assert(list.children.every(row => row.getAttribute('aria-current') === null), 'Explicit gaps clear active highlighting');
    media.currentTime = 47; view.synchronize(media);
    assert.strictEqual(list.children[2].getAttribute('aria-current'), 'true');
    media.currentTime = 50; view.synchronize(media);
    assert.strictEqual(list.children[3].getAttribute('aria-current'), 'true', 'Latest overlapping start wins as on video');
    media.currentTime = 0; view.synchronize(media);
    assert.strictEqual(list.children[0].getAttribute('aria-current'), 'true', 'Replay restores the first section');
    list.children[0].querySelector('[data-section-seek]').focus();
    panel.fire('keydown', {key: 'Escape'}); assert(panel.hidden); assert.strictEqual(doc.activeElement, toggle);
    player.querySelector('[data-seek]').max = 10; view.synchronize(media);
    assert.strictEqual(markers.children.length, 1, 'Markers stay within the preview seek range');
    toggle.fire('click'); list.children[0].querySelector('[data-section-seek]').focus();
    view.select(node({'data-audio-moments': '[]'}));
    assert(toggle.hidden); assert(panel.hidden); assert.strictEqual(markers.children.length, 0);
    assert.strictEqual(doc.activeElement, player.querySelector('[data-player-toggle]'), 'Track changes restore focus out of removed sections');
    view.select(node({'data-audio-moments': '{broken'})); assert(toggle.hidden);
    view.select(node({'data-audio-moments': '{}'})); assert(toggle.hidden);
    assert(layouts > 0, 'Expanding content reserves dock height');
    console.log('Passed: audio sections, marker/list seeking, timing/gaps/overlaps, replay, safe titles, focus, preview markers and tracks without sections.');
};
