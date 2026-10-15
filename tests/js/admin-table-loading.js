const assert = require('assert');
const create = require('../../resources/js/components/admin-table-loading');

function fixture(count = 12) {
    const classes = new Set(), attributes = new Map(), bodyAttributes = new Map();
    const timers = new Map(), frames = [], events = {};
    let timerId = 0, resolveFonts;
    const status = {hidden: true};
    const body = {
        rows: Array.from({length: count}, () => ({cells: ['date', 'id', 'name', 'origin', 'status', 'toggle', 'actions'].map(kind => ({
            attributes: {}, setAttribute(key, value) { this.attributes[key] = value; },
            classList: {contains() { return false; }},
            querySelector(selector) { return kind === 'toggle' && selector === '.status-toggle'; },
            querySelectorAll() { return kind === 'actions' ? [{}, {}] : []; }
        }))})),
        setAttribute(key, value) { bodyAttributes.set(key, value); },
        removeAttribute(key) { bodyAttributes.delete(key); }
    };
    const frame = {
        style: {}, classList: {
            add(...names) { names.forEach(name => classes.add(name)); },
            remove(...names) { names.forEach(name => classes.delete(name)); },
            contains(name) { return classes.has(name); }
        },
        querySelector() { return status; },
        getBoundingClientRect() { return {height: 840}; }
    };
    const table = {
        tBodies: [body], tHead: {rows: [{cells: ['Date', 'ID', 'Name', 'Origin', 'Status', 'Super User', 'Actions'].map(textContent => ({textContent, classList: {add() {}}}))}]},
        closest() { return frame; },
        setAttribute(key, value) { attributes.set(key, value); }
    };
    const win = {
        document: {fonts: {ready: new Promise(resolve => { resolveFonts = resolve; })}},
        setTimeout(callback) { timers.set(++timerId, callback); return timerId; },
        clearTimeout(id) { timers.delete(id); },
        requestAnimationFrame(callback) { frames.push(callback); }
    };
    const loader = create(win);
    const jquery = () => ({on(name, selector, callback) { events[name.split('.')[0]] = callback; return this; }});
    loader.bind(jquery);
    loader.prepare(table);
    return {
        table, frame, body, attributes, bodyAttributes, timers, status,
        busy: () => classes.has('is-loading'),
        emit(name, ...args) { events[name].call(table, {}, ...args); },
        async fontsReady() { resolveFonts(); await Promise.resolve(); },
        paint() { while (frames.length) frames.shift()(); }
    };
}

module.exports = async function () {
    const table = fixture();
    assert(table.busy());
    assert.strictEqual(table.body.rows.length, 12, 'The real rows remain in place during loading');
    assert.strictEqual(table.frame.style.minHeight, '840px');
    assert.strictEqual(table.attributes.get('aria-busy'), 'true');
    assert(table.bodyAttributes.has('inert'), 'Masked row actions must not receive keyboard focus');
    assert.strictEqual(table.body.rows[0].cells[5].attributes['data-table-placeholder'], 'toggle');
    assert.strictEqual(table.body.rows[0].cells[6].attributes['data-table-placeholder'], 'actions');
    table.emit('preInit');
    assert(!table.frame.classList.contains('is-initializing'));
    table.emit('draw'); table.paint();
    assert(table.busy(), 'Wait for fonts before revealing initial content');
    await table.fontsReady(); table.paint();
    assert(!table.busy());
    assert.strictEqual(table.attributes.get('aria-busy'), 'false');
    assert(!table.bodyAttributes.has('inert'));
    assert.strictEqual(table.frame.style.minHeight, '');
    assert(table.status.hidden);

    const racing = fixture();
    racing.emit('draw');
    racing.emit('preXhr');
    await racing.fontsReady(); racing.paint();
    assert(racing.busy(), 'An earlier font/layout callback must not reveal a newer request');
    const current = {}, older = {};
    racing.emit('xhr', {jqXHR: current, iDraw: 3}, null, older);
    assert(racing.busy(), 'Ignore failures from an older request');
    racing.emit('xhr', {jqXHR: current, iDraw: 3}, {draw: 2, error: 'old'}, current);
    assert(racing.busy(), 'Ignore stale responses');
    racing.emit('xhr', {jqXHR: current, iDraw: 3}, null, current);
    assert(!racing.busy(), 'A current failure restores the existing rows');
    assert.strictEqual(racing.timers.size, 0);
    racing.emit('preXhr'); racing.emit('draw'); racing.paint();
    assert(!racing.busy(), 'A later successful retry releases its placeholder');

    const fallback = fixture();
    [...fallback.timers.values()][0]();
    assert(!fallback.busy(), 'A missing DataTables script cannot hide the server rows permanently');
    assert(!fallback.bodyAttributes.has('aria-hidden'));
    const empty = fixture(0);
    assert(!empty.busy(), 'Known empty tables have no fake data rows');
    empty.emit('preXhr');
    assert(!empty.busy());
    assert.strictEqual(empty.timers.size, 0);
    const noResults = fixture();
    noResults.body.rows = [];
    noResults.emit('draw');
    assert(!noResults.busy(), 'An empty search result is revealed immediately');
    console.log('Passed: shared admin table skeleton, font/layout readiness, empty bypass, request races and failure fallback.');
};

if (require.main === module) module.exports().catch(error => { console.error(error); process.exitCode = 1; });
