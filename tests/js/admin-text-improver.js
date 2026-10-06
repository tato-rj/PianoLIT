const assert = require('assert');
const fs = require('fs');
const vm = require('vm');

module.exports = function () {
    function element() {
        return {value: '', disabled: false, readOnly: false, hidden: false, isConnected: true,
            maxLength: -1, attrs: {}, events: {}, textContent: '',
            classList: {add() {}, contains() { return false; }, toggle() {}},
            addEventListener(type, fn) { (this.events[type] || (this.events[type] = [])).push(fn); },
            dispatchEvent(event) { (this.events[event.type] || []).forEach(fn => fn(event)); },
            setAttribute(key, value) { this.attrs[key] = value; }, removeAttribute(key) { delete this.attrs[key]; },
            matches() { return this.disabled; }, focus() { this.focused = true; },
            insertAdjacentElement(where, node) { this.nextElementSibling = node; }};
    }
    const form = element(), field = element(), requests = [], fields = [field], bars = [];
    field.form = form; field.value = 'Original text';
    const docEvents = {};
    let scan;
    const editors = {};
    const richNodes = [{nodeValue: 'Original ', parentElement: {closest() { return null; }}},
        {nodeValue: 'linked text', parentElement: {closest() { return null; }}}];
    const document = {readyState: 'complete', body: {}, querySelectorAll() { return fields; },
        addEventListener(type, fn) { docEvents[type] = fn; },
        createElement() {
            const bar = element(), toggle = element(), options = element(), run = element(), status = element();
            const selects = [element(), element()]; selects.forEach(select => { select.value = 'same'; });
            bar.toggle = toggle; bar.options = options; bar.run = run; bar.status = status; bar.selects = selects;
            bar.querySelector = selector => selector === 'button' ? toggle : selector === '[role="status"]' ? status :
                selector === '.admin-text-improve-options' ? options : run;
            bar.querySelectorAll = () => selects;
            Object.defineProperty(bar, 'innerHTML', {set(value) {
                this.html = value;
                if (value.includes('aria-expanded')) { options.hidden = true; }
            }, get() { return this.html; }});
            bars.push(bar); return bar;
        },
        createTreeWalker() { let index = 0; return {nextNode() { return richNodes[index++] || null; }}; }
    };
    vm.runInNewContext(fs.readFileSync('resources/js/components/admin-text-improver.js', 'utf8'), {
        window: {app: {routes: {improveText: '/text/improve'}, csrfToken: 'session-token'}, tinymce: {get(id) { return editors[id] || null; }}},
        document, WeakMap, NodeFilter: {SHOW_TEXT: 4}, MutationObserver: function (fn) { scan = fn; this.observe = () => {}; },
        Event: function (type) { this.type = type; },
        $: {ajax(options) {
            const request = {options, done(fn) { this.success = fn; return this; },
                fail(fn) { this.failure = fn; return this; }, always(fn) { this.finish = fn; return this; }};
            requests.push(request); return request;
        }}
    });
    let bar = bars[0];
    scan(); assert.strictEqual(bars.length, 1, 'Repeated scans do not attach duplicate controls');
    assert(bar.html.includes('value="same" selected'));
    bar.toggle.dispatchEvent({type: 'click'});
    assert.strictEqual(bar.options.hidden, false);
    assert.strictEqual(bar.toggle.attrs['aria-expanded'], 'true');
    bar.run.dispatchEvent({type: 'click'}); bar.run.dispatchEvent({type: 'click'});
    assert.strictEqual(requests.length, 1);
    assert.strictEqual(requests[0].options.headers['X-CSRF-TOKEN'], 'session-token');
    assert.deepStrictEqual(JSON.parse(requests[0].options.data), {texts: ['Original text'], length: 'same', tone: 'same', max_length: null});
    assert.strictEqual(form.disabled, false);
    let prevented = false;
    docEvents.submit({target: form, preventDefault() { prevented = true; }, stopImmediatePropagation() {}});
    assert(prevented, 'Saving waits for the draft request');
    requests[0].success({texts: ['Improved text']}); requests[0].finish();
    assert.strictEqual(field.value, 'Improved text');
    assert(bar.status.textContent.includes('save the form'));
    assert.strictEqual(bar.run.disabled, false);
    assert.strictEqual(field.attrs['aria-busy'], undefined);

    bar.selects[0].value = 'longer'; bar.selects[1].value = 'formal'; field.maxLength = 30;
    bar.run.dispatchEvent({type: 'click'});
    assert.strictEqual(JSON.parse(requests[1].options.data).tone, 'formal');
    assert.strictEqual(JSON.parse(requests[1].options.data).max_length, 30);
    field.dispatchEvent({type: 'input'}); // Even typing then restoring the same value invalidates it.
    requests[1].success({texts: ['Stale response']}); requests[1].finish();
    assert.strictEqual(field.value, 'Improved text');
    assert(bar.status.textContent.includes('latest text'));
    bar.run.dispatchEvent({type: 'click'}); form.dispatchEvent({type: 'reset'});
    requests[2].success({texts: ['Stale after reset']}); requests[2].finish();
    assert.strictEqual(field.value, 'Improved text');

    for (const output of [{texts: ['x'.repeat(31)]}, {}, {texts: ['']}, {texts: ['one', 'two']}]) {
        bar.run.dispatchEvent({type: 'click'});
        const request = requests[requests.length - 1]; request.success(output); request.finish();
        assert.strictEqual(field.value, 'Improved text');
    }
    for (const xhr of [{status: 500}, {status: 419}, {status: 429},
        {status: 422, responseJSON: {errors: {texts: ['Source is too long']}}}]) {
        bar.run.dispatchEvent({type: 'click'});
        const request = requests[requests.length - 1]; request.failure(xhr); request.finish();
        assert.strictEqual(field.value, 'Improved text'); assert.strictEqual(bar.run.disabled, false);
        assert(bar.status.textContent);
    }
    field.readOnly = true; scan(); let count = requests.length;
    bar.run.dispatchEvent({type: 'click'}); assert.strictEqual(requests.length, count);
    field.readOnly = false; field.disabled = true; scan();
    bar.run.dispatchEvent({type: 'click'}); assert.strictEqual(requests.length, count);
    field.disabled = false; field.value = ' '; scan();
    bar.run.dispatchEvent({type: 'click'}); assert.strictEqual(requests.length, count); assert(field.focused);
    field.value = 'Original';
    bar.run.dispatchEvent({type: 'click'}); field.isConnected = false;
    requests[requests.length - 1].success({texts: ['Removed field draft']}); requests[requests.length - 1].finish();
    assert.strictEqual(field.value, 'Original');
    field.isConnected = true;

    const dynamic = element(); dynamic.form = form; dynamic.value = 'Dynamic text'; fields.push(dynamic); scan();
    assert.strictEqual(bars.length, 2, 'Dynamically added textareas are covered');
    bars[1].run.dispatchEvent({type: 'click'});
    requests[requests.length - 1].success({texts: ['Dynamic draft']}); requests[requests.length - 1].finish();
    assert.strictEqual(dynamic.value, 'Dynamic draft');
    bar.toggle.dispatchEvent({type: 'click'}); // Close and reopen with defaults.
    bar.toggle.dispatchEvent({type: 'click'});
    assert.deepStrictEqual(bar.selects.map(select => select.value), ['same', 'same']);

    const rich = element(), container = element(); rich.id = 'editor'; rich.form = form; fields.push(rich);
    const editor = element(); editor.initialized = true; editor.content = '<p>Original <a href="/link">linked text</a></p>';
    editor.getContainer = () => container; editor.getContent = () => editor.content;
    editor.on = (events, fn) => events.split(' ').forEach(type => editor.addEventListener(type, fn));
    editor.undoManager = {transact(fn) { editor.undo = true; fn(); }};
    editor.setContent = html => { editor.content = html; editor.dispatchEvent({type: 'SetContent'}); };
    editor.save = () => { rich.value = editor.content; };
    editor.setDirty = value => { editor.dirty = value; }; editor.fire = type => editor.dispatchEvent({type});
    editors.editor = editor; scan(); bar = bars[2];
    assert.strictEqual(container.nextElementSibling, bar, 'Button attaches below the rich editor');
    bar.run.dispatchEvent({type: 'click'}); const request = requests[requests.length - 1];
    assert.deepStrictEqual(JSON.parse(request.options.data).texts, ['Original ', 'linked text']);
    request.success({texts: ['Clearer', '<script>inert text</script>']}); request.finish();
    assert.strictEqual(richNodes[0].nodeValue, 'Clearer ', 'Spaces around inline formatting survive');
    assert.strictEqual(richNodes[1].nodeValue, '<script>inert text</script>', 'Output is assigned as text, not HTML');
    assert(editor.dirty && editor.undo);
    assert.strictEqual(rich.value, editor.content);
    console.log('Passed: admin text rewrites, defaults, dynamic fields, limits, CSRF, rich text, stale drafts and failure recovery.');
};
