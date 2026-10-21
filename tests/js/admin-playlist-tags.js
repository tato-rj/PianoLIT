const assert = require('assert');
const fs = require('fs');
const vm = require('vm');

module.exports = async function () {
    const requests = [], listeners = {};
    let rows = [], renderedTags = [], confirmed = false;
    function node(attributes = {}) {
        return {
            attributes, disabled: false, hidden: false, dataset: {}, value: '', textContent: '',
            classList: {values: new Set(), add(value) { this.values.add(value); }, remove(value) { this.values.delete(value); }},
            matches(selector) { return selector.split(',').some(part => this.hasAttribute(part.trim().slice(1, -1))); },
            hasAttribute(name) { return Object.hasOwn(this.attributes, name); },
            setAttribute(name, value) { this.attributes[name] = value; },
            removeAttribute(name) { delete this.attributes[name]; },
            closest(selector) { return selector === '[data-tag-id]' ? this.row || null : (this.matches(selector) ? this : null); },
            focus() { this.focused = true; }, select() { this.selected = true; },
        };
    }
    function row(tag) {
        const item = node(); item.dataset = {tagId: String(tag.id), tagName: tag.name};
        item.label = node(); item.label.textContent = tag.name;
        item.input = node(); item.input.value = tag.name; item.input.row = item;
        item.form = node({'data-tag-edit': ''}); item.form.action = '/tags/' + tag.id; item.form.hidden = true; item.form.row = item;
        item.form.querySelector = () => item.input;
        item.edit = node({'data-tag-start-edit': ''}); item.edit.row = item;
        item.cancel = node({'data-tag-cancel': ''}); item.cancel.row = item;
        item.remove = node({'data-tag-delete': ''}); item.remove.row = item; item.remove.dataset.tagDelete = '/tags/' + tag.id;
        item.save = node(); item.save.row = item;
        item.readActions = node(); item.editActions = node(); item.editActions.hidden = true;
        item.controls = [item.input, item.edit, item.cancel, item.remove, item.save];
        const selectors = {'[data-tag-label]': item.label, '[data-tag-read-actions]': item.readActions, '[data-tag-edit-actions]': item.editActions,
            '[data-tag-edit]': item.form, '[name="name"]': item.input, '[data-tag-start-edit]': item.edit};
        item.querySelector = selector => selectors[selector];
        return item;
    }
    const input = node(), createButton = node(), create = node({'data-tag-create': ''});
    create.action = '/tags'; create.querySelector = () => input; create.reset = () => { input.value = ''; };
    const list = {querySelector(selector) { return rows.find(item => selector === '[data-tag-id="' + item.dataset.tagId + '"]'); }};
    let replacements = 0;
    Object.defineProperty(list, 'innerHTML', {set(html) { assert.strictEqual(html, '<table>updated</table>'); replacements++; rows = renderedTags.map(row); }});
    const error = node(), status = node(), root = node();
    root.isConnected = true; root.dataset.csrf = 'session-csrf';
    root.addEventListener = (name, listener) => { listeners[name] = listener; };
    root.querySelector = selector => ({'[data-tag-create]': create, '[data-tag-list]': list, '[data-tag-error]': error, '[data-tag-status]': status})[selector];
    root.querySelectorAll = selector => {
        const controls = [input, createButton, ...rows.flatMap(item => item.controls)];
        return selector === '[aria-invalid]' ? controls.filter(item => item.hasAttribute('aria-invalid')) : controls;
    };
    const label = node(); label.dataset.playlistFeaturedTag = '1'; label.textContent = 'Theme';
    const select = node(); select.value = '1'; select.children = [];
    select.replaceChildren = () => { select.children = []; };
    select.appendChild = child => { select.children.push(child); };
    const document = {
        querySelector() { return root; },
        querySelectorAll(selector) { return selector === '.js-playlist-tag-select' ? [select] : [label]; },
        createElement() { return node(); },
    };
    function fetch(url, options) {
        return new Promise((resolve, reject) => { requests.push({url, options, resolve, reject}); });
    }
    vm.runInNewContext(fs.readFileSync('resources/js/views/admin-playlist-tags.js', 'utf8'), {document, fetch, window: {confirm() { return confirmed; }}});
    const tick = () => new Promise(resolve => setImmediate(resolve));
    const submit = form => { let prevented = false; listeners.submit({target: form, preventDefault() { prevented = true; }}); assert(prevented, 'Tag forms never navigate.'); };
    const click = button => listeners.click({target: button});
    function respond(tags, message = 'Saved') {
        renderedTags = tags;
        requests.at(-1).resolve({ok: true, json: async () => ({tags, message, html: '<table>updated</table>'})});
    }

    input.value = 'Theme'; submit(create); submit(create);
    assert.strictEqual(requests.length, 1, 'Duplicate submissions share one pending operation.');
    assert.strictEqual(createButton.disabled, true);
    assert.strictEqual(requests[0].options.headers['X-CSRF-TOKEN'], 'session-csrf');
    assert.strictEqual(requests[0].options.credentials, 'same-origin');
    assert.strictEqual(requests[0].options.method, 'POST');
    respond([{id: 1, name: 'Theme'}]); await tick();
    assert.strictEqual(input.value, ''); assert.strictEqual(createButton.disabled, false);

    click(rows[0].edit);
    assert.strictEqual(rows[0].form.hidden, false); assert.strictEqual(rows[0].label.hidden, true);
    assert.strictEqual(createButton.disabled, true, 'Other operations cannot discard an unsaved inline edit.');
    assert.strictEqual(rows[0].input.selected, true);
    rows[0].input.value = 'Mood'; submit(rows[0].form);
    assert.strictEqual(requests.at(-1).options.method, 'PATCH');
    assert.strictEqual(JSON.parse(requests.at(-1).options.body).name, 'Mood');
    requests.at(-1).resolve({ok: false, status: 422, json: async () => ({errors: {name: ['Already used']}})}); await tick();
    assert.strictEqual(error.textContent, 'Already used'); assert.strictEqual(error.hidden, false);
    assert.strictEqual(rows[0].input.value, 'Mood'); assert.strictEqual(rows[0].input.attributes['aria-invalid'], 'true');
    assert.strictEqual(rows[0].save.disabled, false, 'Failure restores the current editor.');
    submit(rows[0].form); respond([{id: 1, name: '<Mood>'}]); await tick();
    assert.strictEqual(label.textContent, '<Mood>', 'Playlist labels use text, not submitted HTML.');
    assert.strictEqual(select.children[1].textContent, '<Mood>'); assert.strictEqual(select.value, '1');
    assert.strictEqual(rows[0].edit.focused, true);
    click(rows[0].edit); rows[0].input.value = 'Discard'; click(rows[0].cancel);
    assert.strictEqual(rows[0].input.value, '<Mood>'); assert.strictEqual(rows[0].form.hidden, true);

    const count = requests.length;
    click(rows[0].remove); assert.strictEqual(requests.length, count, 'Cancel never deletes.');
    confirmed = true; click(rows[0].remove);
    assert.strictEqual(requests.at(-1).options.method, 'DELETE');
    assert.strictEqual(requests.at(-1).options.body, undefined);
    respond([]); await tick();
    assert.strictEqual(label.hidden, true); assert.strictEqual(select.value, ''); assert.strictEqual(rows.length, 0);

    input.value = 'Retry'; submit(create); requests.at(-1).reject(new Error('Offline')); await tick();
    assert.strictEqual(input.value, 'Retry'); assert.strictEqual(error.textContent, 'Offline'); assert.strictEqual(createButton.disabled, false);
    submit(create); requests.at(-1).resolve({ok: false, status: 419, json: async () => ({})}); await tick();
    assert.match(error.textContent, /session has expired/);
    const before = replacements;
    submit(create); root.isConnected = false; respond([{id: 2, name: 'Late'}]); await tick();
    assert.strictEqual(replacements, before, 'Late responses cannot modify a replaced surface.');
    console.log('Passed: tag AJAX creation/edit/cancel/delete, validation/network/session recovery, duplicate guards, safe label/select synchronization and detached responses.');
};
