const assert = require('assert');
const fs = require('fs');
const vm = require('vm');
module.exports = async function () {
    class Node {
        constructor() { this.events = {}; this.nodes = {}; this.dataset = {}; this.style = {}; this.hidden = false; this.disabled = false; this.checked = true; this.value = ''; this.textContent = ''; this.clientWidth = 500; this.children = []; this.classes = new Set(); this.classList = {toggle: (name, value) => value ? this.classes.add(name) : this.classes.delete(name), add: name => this.classes.add(name), remove: name => this.classes.delete(name)}; }
        querySelector(selector) { return this.nodes[selector] || (this.nodes[selector] = new Node()); }
        querySelectorAll(selector) { return this.lists && this.lists[selector] || []; }
        addEventListener(name, handler) { (this.events[name] || (this.events[name] = [])).push(handler); }
        removeEventListener(name, handler) { this.events[name] = (this.events[name] || []).filter(item => item !== handler); }
        fire(name, event = {}) { (this.events[name] || []).forEach(handler => handler(event)); }
        setAttribute() {} removeAttribute() {} append(...nodes) { this.children.push(...nodes); } replaceChildren() { this.children = []; }
        getContext() { return {measureText: text => ({width: text.length * 6}), drawImage() {}}; }
        toDataURL() { return 'data:image/png;base64,AA=='; }
        getBoundingClientRect() { return {top: 0, bottom: 300, height: 300}; }
        matches() { return false; } focus() {} closest() { return modal; }
    }
    const form = new Node(), modal = new Node(), row = new Node(), selected = row.querySelector('[data-escore-select]'), handle = row.querySelector('[data-escore-drag]');
    row.dataset = {escorePiece: '7', eligible: 'true'};
    selected.closest = handle.closest = () => row;
    form.action = '/folder/pdf'; form.elements = {};
    ['title', 'subtitle', 'comment', 'bottom_text', 'color', '_token', 'page_numbers', 'composer_names', 'include_edition', 'blank_pages'].forEach(name => { form.elements[name] = new Node(); });
    form.elements.title.value = 'My book'; form.elements.color.value = '#00a2ff'; form.elements._token.value = 'session-token';
    form.lists = {'[data-escore-piece]': [row], '[data-escore-select]': [selected], '[data-escore-drag]': [handle]};
    const requests = [], renderedPages = [], timers = new Map(); let nextTimer = 0, destroyed = 0;
    const document = new Node();
    Object.assign(document, {readyState: 'loading', createElement: () => new Node(), querySelector: () => null});
    const context = vm.createContext({module: {exports: {}}, document,
        window: {pdfjsLib: {GlobalWorkerOptions: {}, getDocument: () => ({promise: Promise.resolve({numPages: 3, destroy() { destroyed++; }, getPage: async number => { renderedPages.push(number); return {getViewport: ({scale}) => ({width: 612 * scale, height: 792 * scale}), render: () => ({promise: Promise.resolve()})}; }})})}},
        Uint8Array, atob: text => Buffer.from(text, 'base64').toString('binary'),
        AbortController: class { constructor() { this.signal = {aborted: false}; } abort() { this.signal.aborted = true; } },
        FormData: class { constructor() { this.values = {}; } set(key, value) { this.values[key] = value; } append(key, value) { (this.values[key] || (this.values[key] = [])).push(value); } },
        fetch: (url, options) => new Promise((resolve, reject) => requests.push({url, options, resolve, reject})),
        setTimeout: callback => { timers.set(++nextTimer, callback); return nextTimer; }, clearTimeout: id => timers.delete(id), console
    });
    vm.runInContext(fs.readFileSync('resources/js/views/escore.js', 'utf8'), context);
    context.module.exports.initWizard(form);
    const settle = async () => { for (let i = 0; i < 4; i++) await new Promise(resolve => setImmediate(resolve)); };
    const response = pages => ({ok: true, headers: {get: () => 'application/json'}, json: async () => ({pdf: Buffer.from('%PDF').toString('base64'), pages, sections: {cover: 1, title: 0, index: 1, edition: 0, blank: 0}, entries: [{id: 7, title: 'Solo', composer: 'Composer', pages: 1, start: 3}]})});
    modal.fire('shown.bs.modal');
    assert.strictEqual(requests.length, 1);
    assert.strictEqual(requests[0].options.method, 'POST');
    assert.strictEqual(requests[0].options.headers['X-CSRF-TOKEN'], 'session-token');
    assert.deepStrictEqual(Array.from(requests[0].options.body.values['piece_ids[]']), ['7']);
    form.fire('input', {target: form.elements.title});
    assert(requests[0].options.signal.aborted, 'Editing cancels the previous preview');
    form.querySelector('[data-escore-retry]').fire('click');
    requests[1].resolve(response(3)); await settle();
    assert.strictEqual(form.querySelector('[data-escore-status]').textContent, 'Preview ready · 3 pages');
    renderedPages.length = 0;
    form.querySelector('[data-escore-next]').fire('click'); await settle();
    assert.deepStrictEqual(renderedPages, [2], 'Step two uses one main preview for the contents page');
    renderedPages.length = 0;
    form.querySelector('[data-escore-next-page]').fire('click'); await settle();
    assert.deepStrictEqual(renderedPages, [3], 'Step two pager navigates the same main preview');
    form.querySelector('[data-escore-back]').fire('click'); await settle();
    requests[0].resolve(response(99)); await settle();
    assert.strictEqual(form.querySelector('[data-escore-status]').textContent, 'Preview ready · 3 pages', 'An older response cannot replace the latest PDF');
    form.fire('input', {target: form.elements.title});
    form.querySelector('[data-escore-retry]').fire('click');
    requests[2].reject(new Error('Preview failed')); await settle();
    assert.strictEqual(form.querySelector('[data-escore-retry]').hidden, false, 'Failure exposes retry');
    assert.strictEqual(form.querySelector('[data-escore-status]').textContent, 'Preview failed');
    form.querySelector('[data-escore-retry]').fire('click');
    modal.fire('hidden.bs.modal');
    assert(requests[3].options.signal.aborted, 'Closing cancels the current request');
    assert(destroyed > 0, 'Closing releases the loaded PDF');
    requests[3].resolve(response(88)); await settle();
    assert.notStrictEqual(form.querySelector('[data-escore-status]').textContent, 'Preview ready · 88 pages');
    assert.strictEqual(timers.size, 0, 'Closing clears scheduled previews');
    selected.checked = false;
    modal.fire('shown.bs.modal');
    assert.strictEqual(requests.length, 4, 'Empty selections never submit a preview');
    assert.strictEqual(form.querySelector('[data-escore-next]').disabled, true, 'Empty selections cannot advance or download');
    const second = new Node(), third = new Node(), rows = form.lists['[data-escore-piece]'];
    second.dataset = {escorePiece: '8', eligible: 'true'}; third.dataset = {escorePiece: '9', eligible: 'true'};
    rows.push(second, third); selected.checked = true;
    for (const item of rows) {
        item.closest = () => item;
        item.getBoundingClientRect = () => ({top: rows.indexOf(item) * 50, height: 50});
        item.before = moving => { rows.splice(rows.indexOf(moving), 1); rows.splice(rows.indexOf(item), 0, moving); };
        item.after = moving => { rows.splice(rows.indexOf(moving), 1); rows.splice(rows.indexOf(item) + 1, 0, moving); };
    }
    form.contains = item => rows.includes(item);
    document.elementFromPoint = () => third;
    const event = {button: 0, pointerId: 1, preventDefault() {}};
    handle.fire('pointerdown', event);
    document.fire('pointermove', {pointerId: 2, clientX: 0, clientY: 140});
    assert.deepStrictEqual(rows.map(item => item.dataset.escorePiece), ['7', '8', '9'], 'Other pointers cannot move this drag');
    document.fire('pointermove', {pointerId: 1, clientX: 0, clientY: 140});
    document.fire('pointerup', {pointerId: 1});
    assert.deepStrictEqual(rows.map(item => item.dataset.escorePiece), ['8', '9', '7'], 'Drop moves the piece without number inputs');
    assert(!row.classes.has('dragging'), 'Drop clears the drag state');
    assert.strictEqual(document.events.pointermove.length, 0, 'Drop removes pointer listeners');
    form.querySelector('[data-escore-retry]').fire('click');
    assert.deepStrictEqual(Array.from(requests[4].options.body.values['piece_ids[]']), ['8', '9', '7'], 'Preview submits the dropped order');
    handle.fire('pointerdown', event); document.fire('pointercancel', {pointerId: 1});
    assert(!row.classes.has('dragging'), 'Cancellation clears the drag state');
    handle.fire('pointerdown', event); modal.fire('hidden.bs.modal');
    assert(!row.classes.has('dragging'), 'Closing clears an active drag');
    console.log('Passed: eScore drag/drop export order without number inputs, pointer cleanup, session POST, preview races, failures/retry and modal cleanup.');
};
