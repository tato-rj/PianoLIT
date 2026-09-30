const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

module.exports = function () {
    const document = {};
    const handlers = {};
    const bodyClasses = new Set();
    const empty = {length: 0, hasClass() { return false; }};
    const panels = ['options', 'notifications'].map(id => ({
        id, length: 1, open: false, visible: false, pending: null,
        stop(clearQueue, finish) {
            assert(clearQueue && finish, 'Cancel queued animations on every state change');
            this.pending = null;
            return this;
        },
        toggleClass(name, active) { this.open = active; return this; },
        hasClass() { return this.open; },
        find() { return {css() {}}; },
        fadeIn() { this.pending = 'open'; },
        fadeOut() { this.pending = 'close'; },
        finish() { this.visible = this.pending === 'open'; this.pending = null; }
    }));
    let showShare;
    const $ = value => {
        if (value === document) return {on(event, selector, handler) { handlers[selector + ':' + event] = handler; }};
        if (value === 'body') return {toggleClass(name, active) {
            if (active) bodyClasses.add(name);
            else bodyClasses.delete(name);
        }};
        if (value === '.fixed-panel.is-open') return {length: panels.filter(panel => panel.open).length};
        if (value === '.fixed-panel') return {trigger(event) {
            panels.forEach(panel => handlers['.fixed-panel:' + event].call(panel));
        }};
        if (value === '#share-modal') return {on(event, callback) { showShare = callback; }};
        if (typeof value !== 'string') return value;
        if (value === '#options') return panels[0];
        if (value === '#notifications') return panels[1];
        if (value === '#missing') return empty;
        return {click() {}, bind() {}, keypress() {}, on() {}};
    };
    const context = vm.createContext({document, $});
    for (const name of ['modals', 'triggers']) {
        vm.runInContext(fs.readFileSync(path.join(__dirname, '../../resources/js/components/' + name + '.js'), 'utf8'), context);
    }
    const toggle = id => handlers['[data-toggle="fixed-panel"]:click'].call({
        attr() { return '#' + id; }, removeClass() {}
    });
    const close = panel => handlers['button[data-dismiss="fixed-panel"], .fixed-panel .panel-overlay:click'].call({
        closest() { return panel; }
    });
    const locked = () => bodyClasses.has('fixed-panel-open');

    close(empty); // The favorites button in the page header has this dismiss attribute.
    toggle('missing');
    assert.strictEqual(locked(), false);

    toggle('options');
    assert.strictEqual(locked(), true);
    close(panels[0]);
    close(panels[0]);
    panels[0].finish();
    assert.strictEqual(locked(), false, 'Repeated dismissal never locks the body again');
    assert.strictEqual(panels[0].visible, false);

    toggle('options'); toggle('options'); toggle('options'); close(panels[0]);
    panels[0].finish();
    assert.strictEqual(locked(), false, 'Rapid clicks end with the requested closed state');
    assert.strictEqual(panels[0].visible, false);

    toggle('options'); toggle('notifications'); close(panels[0]);
    assert.strictEqual(locked(), true, 'Another open side panel keeps the scroll lock');
    close(panels[1]);
    assert.strictEqual(locked(), false);

    bodyClasses.add('modal-open');
    bodyClasses.add('score-editor-fullscreen-open');
    toggle('options');
    showShare(); showShare();
    assert.strictEqual(locked(), false, 'Opening Share only closes side panels');
    assert(panels.every(panel => !panel.open));
    assert(bodyClasses.has('modal-open') && bodyClasses.has('score-editor-fullscreen-open'), 'Other scroll locks remain owned by their components');
};
