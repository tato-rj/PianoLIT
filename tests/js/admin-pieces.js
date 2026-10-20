const assert = require('assert');
const fs = require('fs');
const vm = require('vm');

module.exports = async function () {
    function element() {
        return {events: {}, dataset: {}, textContent: '', hidden: false, attrs: {},
            addEventListener(name, fn) { this.events[name] = fn; },
            classList: {toggle() {}},
            setAttribute(name, value) { this.attrs[name] = value; },
            removeAttribute(name) { delete this.attrs[name]; },
            replaceChildren() { this.innerHTML = ''; },
        };
    }
    const modal = element(), content = element(), status = element(), retry = element(), title = element();
    modal.querySelector = selector => ({'[data-piece-edit-content]': content, '[data-piece-edit-status]': status, '[data-piece-edit-retry]': retry, '.modal-title': title})[selector];
    const badge = element(), count = element(), label = element();
    badge.querySelector = selector => selector === '[data-piece-tags-count]' ? count : label;
    const pending = [];
    function fetch(url, options) { return new Promise((resolve, reject) => pending.push({url, options, resolve, reject})); }
    const document = {getElementById: id => id === 'piece-quick-edit' ? modal : badge};
    vm.runInNewContext(fs.readFileSync('resources/js/views/admin-pieces.js', 'utf8'), {document, fetch, AbortController, window: {app: {csrfToken: 'session-token'}}});
    const first = {dataset: {pieceEditUrl: '/pieces/1/load-tags', pieceEditTitle: 'Tags · First'}};
    const second = {dataset: {pieceEditUrl: '/pieces/2/load-levels', pieceEditTitle: 'Level · Second'}};
    const flush = () => new Promise(resolve => setImmediate(resolve));
    const ok = html => ({ok: true, redirected: false, text: async () => html});
    modal.events['show.bs.modal']({relatedTarget: first});
    modal.events['show.bs.modal']({relatedTarget: second});
    assert.strictEqual(pending[0].options.signal.aborted, true);
    pending[1].resolve(ok('Second editor'));
    await flush();
    pending[0].resolve(ok('Stale first editor'));
    await flush();
    assert.strictEqual(content.innerHTML, 'Second editor', 'Older loads cannot replace the current editor');
    assert.strictEqual(title.textContent, 'Level · Second');
    modal.events['show.bs.modal']({relatedTarget: first});
    pending[2].resolve({ok: false});
    await flush();
    assert.strictEqual(retry.hidden, false);
    retry.events.click();
    pending[3].resolve(ok('Retried editor'));
    await flush();
    assert.strictEqual(retry.hidden, true);
    assert.strictEqual(content.innerHTML, 'Retried editor');

    const group = element();
    group.dataset = {url: '/pieces/1/update-tag', badge: 'piece-tags-1'};
    group.hasAttribute = () => false;
    const input = {tagName: 'INPUT', value: '7', checked: true, closest: () => group};
    content.querySelectorAll = () => [input];
    let saving = content.events.change({target: input});
    assert.strictEqual(input.disabled, true);
    let prevented = false;
    modal.events['hide.bs.modal']({preventDefault() { prevented = true; }});
    assert.strictEqual(prevented, true, 'Pending saves finish before switching piece editors');
    assert.strictEqual(pending[4].options.headers['X-CSRF-TOKEN'], 'session-token');
    pending[4].resolve({ok: false});
    await saving;
    assert.strictEqual(input.checked, false, 'A failed tag update restores the selection');
    assert.strictEqual(input.disabled, false);
    input.checked = true;
    saving = content.events.change({target: input});
    pending[5].resolve({ok: true, json: async () => ({count: 3})});
    await saving;
    assert.strictEqual(count.textContent, 3);

    const previous = {value: '1', checked: false};
    group.dataset = {url: '/pieces/1/update-level', badge: 'piece-level-1', oldLevel: '1'};
    group.hasAttribute = () => true;
    input.value = '2';
    content.querySelectorAll = () => [previous, input];
    saving = content.events.change({target: input});
    pending[6].reject(new Error('Offline'));
    await saving;
    assert.strictEqual(previous.checked, true);
    assert.strictEqual(input.checked, false);
    assert.strictEqual(previous.disabled, false);
    input.checked = true;
    saving = content.events.change({target: input});
    pending[7].resolve({ok: true, json: async () => ({level_id: 2, level_name: 'Beginner', extended_level_name: 'Early beginner'})});
    await saving;
    assert.strictEqual(label.textContent, 'Early beginner');
    assert.strictEqual(group.dataset.oldLevel, '2');
    modal.events['hidden.bs.modal']();
    assert.strictEqual(content.innerHTML, '');
    console.log('Passed: piece quick-edit stale loads, retry, CSRF, serialized saves, rollback and control recovery.');
};
