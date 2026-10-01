const assert = require('assert');
const fs = require('fs');
const vm = require('vm');

function element() {
    return {
        dataset: {}, events: {}, children: [], hidden: false, disabled: false, textContent: '', value: '', html: '',
        addEventListener(type, handler) { this.events[type] = handler; },
        setAttribute() {}, reportValidity() { return true; },
        insertAdjacentHTML(position, html) { this.html += html; },
        querySelector() { return null; }, querySelectorAll() { return []; },
        appendChild(child) { this.children.push(child); }
    };
}

async function settle() { for (let i = 0; i < 8; i++) await Promise.resolve(); }

async function main() {
    const ids = {};
    for (const id of ['piece-timeline-admin', 'timeline-search', 'reference-year', 'timeline-find', 'timeline-more', 'timeline-search-status', 'timeline-candidates', 'timeline-saved', 'timeline-empty']) ids[id] = element();
    ids['piece-timeline-admin'].dataset = {pieceId: '1', discoverUrl: '/discover', saveUrl: '/save', csrf: 'csrf'};
    const requests = [];
    const storage = new Map();
    const context = {
        document: {getElementById: id => ids[id]},
        sessionStorage: {getItem: key => storage.get(key), setItem: (key, value) => storage.set(key, value), removeItem: key => storage.delete(key)},
        window: {axios: {post(url, data, options) {
            return new Promise((resolve, reject) => requests.push({url, data, options, resolve, reject}));
        }}}
    };
    vm.runInNewContext(fs.readFileSync('resources/js/views/piece-timeline-admin.js', 'utf8'), context);
    const submit = () => ids['timeline-search'].events.submit({preventDefault() {}});
    ids['reference-year'].value = '1730';
    submit();
    assert.strictEqual(ids['timeline-find'].disabled, true);
    assert.strictEqual(requests[0].data.search_id, null);
    assert.strictEqual(requests[0].options.headers['X-CSRF-TOKEN'], 'csrf');
    requests[0].resolve({data: {search_id: 'search-1', count: 10, has_more: true, range: 3, html: 'first ten'}});
    await settle();
    assert.strictEqual(ids['timeline-find'].disabled, false);
    assert.strictEqual(ids['timeline-more'].hidden, false);
    ids['timeline-more'].events.click();
    assert.strictEqual(requests[1].data.search_id, 'search-1');
    requests[1].reject({response: {status: 503, data: {message: 'Wikimedia is unavailable right now.'}}});
    await settle();
    assert.strictEqual(ids['timeline-find'].disabled, false);
    assert.strictEqual(ids['timeline-more'].disabled, false);
    assert.strictEqual(ids['timeline-candidates'].html, 'first ten');
    assert.strictEqual(ids['timeline-search-status'].textContent, 'Wikimedia is unavailable right now.');
    ids['timeline-more'].events.click();
    requests[2].resolve({data: {search_id: 'search-1', count: 10, has_more: true, range: 3, html: 'next ten'}});
    await settle();
    assert.strictEqual(ids['timeline-candidates'].html, 'first tennext ten');

    const button = element();
    const card = element();
    const saveStatus = element();
    card.dataset.candidate = 'Q1:work:1730';
    card.querySelector = () => saveStatus;
    button.closest = () => card;
    ids['timeline-candidates'].querySelectorAll = () => [button];
    const clickSave = () => ids['timeline-candidates'].events.click({target: {closest: () => button}});
    clickSave();
    clickSave();
    assert.strictEqual(requests.length, 4, 'Repeated clicks cannot send a duplicate Save');
    assert.strictEqual(requests[3].data.search_id, 'search-1');
    assert.strictEqual(button.disabled, true);
    assert.strictEqual(ids['timeline-find'].disabled, true, 'Search cannot race with Save');
    requests[3].reject({response: {status: 500, data: {message: 'Internal server detail'}}});
    await settle();
    assert.strictEqual(button.disabled, false);
    assert.strictEqual(saveStatus.textContent, 'Could not save this event. Please try again.');
    clickSave();
    requests[4].resolve({data: {id: 1, html: 'saved event'}});
    await settle();
    assert.strictEqual(button.disabled, true);
    assert.strictEqual(button.textContent, 'Saved');
    assert.strictEqual(ids['timeline-empty'].hidden, true);
    assert.strictEqual(ids['timeline-saved'].html, 'saved event');
    assert.strictEqual(ids['timeline-find'].disabled, false);

    submit();
    assert.strictEqual(requests[5].data.search_id, null, 'New search resets the editing cursor');
    requests[5].resolve({data: {search_id: 'search-2', count: 10, has_more: true, range: 3, html: 'new results'}});
    await settle();
    ids['timeline-more'].events.click();
    requests[6].reject({response: {status: 422, data: {errors: {search_id: ['This search has expired. Start a new search.']}}}});
    await settle();
    assert.strictEqual(ids['timeline-more'].hidden, true);
    assert.strictEqual(storage.size, 0);
    assert.strictEqual(ids['timeline-find'].disabled, false);
    console.log('Passed: timeline search pagination, retries, CSRF, save races/idempotency, and expired sessions.');
}
main().catch(error => { console.error(error); process.exitCode = 1; });
