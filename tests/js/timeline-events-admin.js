const assert = require('assert');
const fs = require('fs');
const vm = require('vm');

function element() {
    return {
        dataset: {}, events: {}, children: [], hidden: false, disabled: false, open: false, value: '', html: '',
        get textContent() { return this.text || ''; },
        set textContent(value) { this.text = String(value); this.children = []; this.html = ''; },
        classList: {add() {}, remove() {}},
        addEventListener(type, handler) { this.events[type] = handler; },
        setAttribute() {}, reportValidity() { return true; }, focus() { this.focused = true; },
        insertAdjacentHTML(position, html) { this.html += html; },
        querySelector(selector) { return this.querySelectorAll(selector)[0] || null; },
        querySelectorAll(selector) {
            const descendants = [];
            function visit(node) { node.children.forEach(child => { descendants.push(child); visit(child); }); }
            visit(this);
            return descendants.filter(node => {
                if (selector === '[data-candidate]') return node.dataset.candidate !== undefined;
                if (selector === '[data-event-id]') return node.dataset.eventId !== undefined;
                if (selector === '[data-decade]') return node.dataset.decade !== undefined;
                const eventId = selector.match(/^\[data-event-id="([^"]+)"\]$/);
                if (eventId) return node.dataset.eventId === eventId[1];
                const decade = selector.match(/^\[data-decade="([^"]+)"\]$/);
                if (decade) return node.dataset.decade === decade[1];
                const period = selector.match(/^\[data-period="([^"]+)"\]$/);
                if (period) return node.dataset.period === period[1];
                if (selector.startsWith('.')) return (node.className || '').split(' ').includes(selector.slice(1));
                return false;
            });
        },
        appendChild(child) {
            if (child.parentElement) child.parentElement.children = child.parentElement.children.filter(node => node !== child);
            child.parentElement = this;
            this.children.push(child);
        },
        remove() {
            if (this.parentElement) this.parentElement.children = this.parentElement.children.filter(node => node !== this);
            this.parentElement = null;
        }
    };
}

async function settle() { for (let i = 0; i < 8; i++) await Promise.resolve(); }

async function main() {
    const ids = {};
    for (const id of ['timeline-events-admin', 'timeline-search', 'reference-year', 'timeline-find', 'timeline-more', 'timeline-search-status', 'timeline-candidates', 'timeline-saved', 'timeline-empty', 'timeline-results-heading', 'timeline-candidate-count', 'timeline-saved-count', 'page-heading']) ids[id] = element();
    ids['timeline-events-admin'].dataset = {discoverUrl: '/discover', saveUrl: '/save', csrf: 'csrf'};
    ids['page-heading'].textContent = 'Timeline events';
    const requests = [];
    const storage = new Map([['pianolit.timeline.library', JSON.stringify({year: '1730', searchId: 'old-search'})]]);
    const context = {
        document: {body: element(), createElement: () => element(), getElementById: id => ids[id]},
        sessionStorage: {getItem: key => storage.get(key), setItem: (key, value) => storage.set(key, value), removeItem: key => storage.delete(key)},
        window: {axios: {post(url, data, options) {
            return new Promise((resolve, reject) => requests.push({url, data, options, resolve, reject}));
        }, delete(url, options) {
            return new Promise((resolve, reject) => requests.push({url, options, method: 'DELETE', resolve, reject}));
        }}}
    };
    vm.runInNewContext(fs.readFileSync('resources/js/views/timeline-events-admin.js', 'utf8'), context);
    assert.strictEqual(ids['reference-year'].value, '1730', 'Restore the manually selected year');
    assert.strictEqual(ids['timeline-more'].hidden, true, 'A stored cursor must not show More without results');
    assert.strictEqual(ids['timeline-results-heading'].hidden, true);
    assert.strictEqual(ids['page-heading'].textContent, 'Timeline events');
    const submit = () => ids['timeline-search'].events.submit({preventDefault() {}});
    ids['reference-year'].value = '1730';
    submit();
    assert.strictEqual(ids['timeline-find'].disabled, true);
    assert.strictEqual(requests[0].data.search_id, 'old-search', 'A reload continues the server exclusions without revealing More');
    assert.strictEqual(requests[0].options.headers['X-CSRF-TOKEN'], 'csrf');
    requests[0].resolve({data: {search_id: 'search-1', count: 10, has_more: true, range: 10, start_year: 1730, end_year: 1740, html: 'first ten'}});
    await settle();
    assert.strictEqual(ids['timeline-find'].disabled, false);
    assert.strictEqual(ids['timeline-more'].hidden, false);
    assert.strictEqual(ids['timeline-results-heading'].hidden, false);
    assert.strictEqual(ids['timeline-candidate-count'].textContent, '10');
    ids['timeline-more'].events.click();
    assert.strictEqual(requests[1].data.search_id, 'search-1');
    requests[1].reject({response: {status: 503, data: {message: 'Wikimedia is unavailable right now.'}}});
    await settle();
    assert.strictEqual(ids['timeline-find'].disabled, false);
    assert.strictEqual(ids['timeline-more'].disabled, false);
    assert.strictEqual(ids['timeline-candidates'].html, 'first ten');
    assert.strictEqual(ids['timeline-search-status'].textContent, 'Wikimedia is unavailable right now.');
    ids['timeline-more'].events.click();
    requests[2].resolve({data: {search_id: 'search-1', count: 10, has_more: true, range: 10, start_year: 1730, end_year: 1740, html: 'next ten'}});
    await settle();
    assert.strictEqual(ids['timeline-candidates'].html, 'first tennext ten');
    assert.strictEqual(ids['timeline-candidate-count'].textContent, '20');

    const button = element();
    const card = element();
    const saveStatus = element();
    card.dataset.candidate = 'Q1:work:1730';
    card.querySelector = selector => selector === '.timeline-save' ? button : saveStatus;
    button.closest = () => card;
    ids['timeline-candidates'].querySelectorAll = selector => selector === '[data-candidate]' ? [card] : [button];
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
    requests[4].resolve({data: {id: 1, count: 1, html: 'saved event'}});
    await settle();
    assert.strictEqual(button.disabled, true);
    assert.strictEqual(button.textContent, 'Saved');
    assert.strictEqual(ids['timeline-empty'].hidden, true);
    assert.strictEqual(ids['timeline-saved'].html, 'saved event');
    assert.strictEqual(ids['timeline-saved-count'].textContent, '1');
    assert.strictEqual(ids['page-heading'].textContent, 'Timeline events');
    assert.strictEqual(ids['timeline-find'].disabled, false);

    submit();
    assert.strictEqual(requests[5].data.search_id, null, 'New search resets the editing cursor');
    requests[5].resolve({data: {search_id: 'search-2', count: 10, has_more: true, range: 10, start_year: 1730, end_year: 1740, html: 'new results'}});
    await settle();
    ids['timeline-more'].events.click();
    requests[6].reject({response: {status: 422, data: {errors: {search_id: ['This search has expired. Start a new search.']}}}});
    await settle();
    assert.strictEqual(ids['timeline-more'].hidden, true);
    assert.strictEqual(storage.size, 0);
    assert.strictEqual(ids['timeline-find'].disabled, false);

    ids['reference-year'].events.input();
    assert.strictEqual(ids['timeline-more'].hidden, true, 'Changing the year hides More');
    assert.strictEqual(ids['timeline-results-heading'].hidden, true);
    submit();
    requests[7].resolve({data: {search_id: 'empty-search', count: 0, has_more: true, range: 10, start_year: 1730, end_year: 1740, html: ''}});
    await settle();
    assert.strictEqual(ids['timeline-more'].hidden, true, 'An empty API response must not reveal More');
    assert.strictEqual(ids['timeline-results-heading'].hidden, true);
    submit();
    assert.strictEqual(requests[8].data.search_id, 'empty-search', 'The single Find button can continue checking an empty batch');
    requests[8].resolve({data: {search_id: 'empty-search', count: 1, has_more: true, range: 10, start_year: 1730, end_year: 1740, html: 'one result'}});
    await settle();
    assert.strictEqual(ids['timeline-more'].hidden, false);
    ids['timeline-more'].events.click();
    requests[9].resolve({data: {search_id: 'empty-search', count: 0, has_more: false, range: 10, start_year: 1730, end_year: 1740, html: ''}});
    await settle();
    assert.strictEqual(ids['timeline-more'].hidden, true, 'Exhausted searches hide More');
    assert.strictEqual(ids['timeline-results-heading'].hidden, false, 'Existing results remain visible after exhaustion');

    const removeForm = element();
    const removeButton = element();
    const removeStatus = element();
    const row = element();
    let removed = false;
    row.remove = () => { removed = true; };
    removeForm.action = '/timeline/1';
    removeForm.closest = selector => selector === '.timeline-remove' ? removeForm : row;
    removeForm.querySelector = selector => selector === 'button[type="submit"]' ? removeButton : removeStatus;
    ids['timeline-saved'].querySelectorAll = selector => selector === 'button[type="submit"]' ? [removeButton] : [];
    const remove = () => ids['timeline-saved'].events.submit({target: removeForm, preventDefault() {}});
    remove();
    remove();
    assert.strictEqual(requests.length, 11, 'Repeated removal submits cannot send duplicate deletes');
    assert.strictEqual(requests[10].method, 'DELETE');
    assert.strictEqual(requests[10].options.headers['X-CSRF-TOKEN'], 'csrf');
    assert.strictEqual(removeButton.disabled, true);
    assert.strictEqual(ids['timeline-find'].disabled, true);
    requests[10].reject({response: {status: 500}});
    await settle();
    assert.strictEqual(removed, false, 'A failed deletion preserves the event');
    assert.strictEqual(ids['page-heading'].textContent, 'Timeline events');
    assert.strictEqual(removeButton.disabled, false);
    assert.strictEqual(removeStatus.textContent, 'Could not remove this event. Please try again.');
    remove();
    requests[11].resolve({data: {id: 1, source_id: card.dataset.candidate, count: 0}});
    await settle();
    assert.strictEqual(removed, true);
    assert.strictEqual(ids['timeline-saved-count'].textContent, '0');
    assert.strictEqual(ids['page-heading'].textContent, 'Timeline events');
    assert.strictEqual(ids['timeline-empty'].hidden, false);
    assert.strictEqual(button.disabled, false, 'The removed candidate can be saved again');
    assert.strictEqual(button.textContent, 'Save event');
    clickSave();
    requests[12].resolve({data: {id: 2, count: 4, html: 'saved again'}});
    await settle();
    assert.strictEqual(ids['page-heading'].textContent, 'Timeline events', 'Use the authoritative server count');
    assert.strictEqual(ids['timeline-saved-count'].textContent, '4');

    const cardFor = (id, date) => {
        const card = element();
        card.dataset = {candidate: id, year: date.slice(0, 4), date};
        return card;
    };

    const libraryIds = {};
    Object.keys(ids).forEach(id => { libraryIds[id] = element(); });
    libraryIds['timeline-events-admin'].dataset = {discoverUrl: '/library/discover', saveUrl: '/library/save', csrf: 'library-csrf'};
    libraryIds['timeline-events-admin'].querySelector = () => libraryIds['page-heading'];
    libraryIds['timeline-saved-count'].textContent = '2';
    libraryIds['page-heading'].textContent = 'Timeline events';
    const libraryStorage = new Map();
    const libraryRequests = [];
    const libraryContext = {
        document: {body: element(), createElement: () => element(), getElementById: id => libraryIds[id]},
        sessionStorage: {getItem: key => libraryStorage.get(key), setItem: (key, value) => libraryStorage.set(key, value), removeItem: key => libraryStorage.delete(key)},
        window: {axios: {post(url, data, options) {
            return new Promise((resolve, reject) => libraryRequests.push({url, data, options, resolve, reject}));
        }}}
    };
    vm.runInNewContext(fs.readFileSync('resources/js/views/timeline-events-admin.js', 'utf8'), libraryContext);
    assert.strictEqual(libraryIds['reference-year'].value, '', 'The library starts with a blank reference year');
    assert.strictEqual(libraryIds['page-heading'].textContent, 'Timeline events', 'The shared page heading has no live event count');
    assert.strictEqual(libraryIds['timeline-more'].hidden, true);
    libraryIds['reference-year'].value = '1800';
    libraryIds['timeline-search'].events.submit({preventDefault() {}});
    assert.strictEqual(libraryRequests[0].url, '/library/discover');
    assert.strictEqual(libraryRequests[0].data.search_id, null);
    libraryRequests[0].resolve({data: {search_id: 'library-search', count: 0, has_more: false, range: 10, start_year: 1800, end_year: 1810, html: ''}});
    await settle();
    assert.strictEqual(libraryIds['timeline-search-status'].textContent, 'No more events found from 1800 to 1810. Try a different reference year.');
    assert.strictEqual(libraryIds['timeline-more'].hidden, true);
    libraryStorage.set('pianolit.timeline.library', JSON.stringify({year: '1850', searchId: 'library-resume'}));
    vm.runInNewContext(fs.readFileSync('resources/js/views/timeline-events-admin.js', 'utf8'), libraryContext);
    assert.strictEqual(libraryIds['reference-year'].value, '1850', 'The library restores its own reference year');
    libraryIds['timeline-search'].events.submit({preventDefault() {}});
    assert.strictEqual(libraryRequests[1].data.search_id, 'library-resume');

    const libraryCards = [cardFor('after', '1860-03-01'), cardFor('same', '1850-01-07'), cardFor('before', '1853-05-02')];
    const libraryNext = [cardFor('earlier', '1851-01-01'), cardFor('later', '1857-08-09')];
    const libraryCandidateRoot = libraryIds['timeline-candidates'];
    libraryCandidateRoot.insertAdjacentHTML = function (position, html) {
        (html === 'library-first' ? libraryCards : libraryNext).forEach(card => this.appendChild(card));
    };
    libraryRequests[1].resolve({data: {search_id: 'library-resume', count: 3, has_more: true, range: 10, start_year: 1850, end_year: 1860, html: 'library-first'}});
    await settle();
    assert.deepStrictEqual(libraryCandidateRoot.children.map(card => card.dataset.candidate), ['same', 'before', 'after'], 'Library results form one chronological list');
    assert.strictEqual(libraryIds['timeline-search-status'].textContent, '3 new events · 1850–1860. Save the ones you want to keep.');
    assert.strictEqual(libraryCandidateRoot.querySelector('.timeline-candidate-group-heading'), null, 'The library has no period headings');
    libraryCards[1].dataset.saved = 'true';
    libraryIds['timeline-more'].events.click();
    libraryRequests[2].resolve({data: {search_id: 'library-resume', count: 2, has_more: false, range: 10, start_year: 1850, end_year: 1860, html: 'library-next'}});
    await settle();
    assert.deepStrictEqual(libraryCandidateRoot.children.map(card => card.dataset.candidate), ['same', 'earlier', 'before', 'later', 'after'], 'More merges into the same sorted list');
    assert.strictEqual(libraryCandidateRoot.children[0], libraryCards[1], 'Sorting retains the original saved card');
    assert.strictEqual(libraryCards[1].dataset.saved, 'true');
    assert.strictEqual(libraryIds['page-heading'].textContent, 'Timeline events');
    // Exercise subject controls independently of saved-event/grouping fixtures.
    const filterIds = {};
    Object.keys(ids).forEach(id => { filterIds[id] = element(); });
    filterIds['timeline-events-admin'].dataset = {discoverUrl: '/discover', saveUrl: '/save'};
    filterIds['timeline-events-admin'].querySelector = () => filterIds['page-heading'];
    const types = ['music', 'art', 'literature', 'science', 'history', 'people'];
    const inputs = types.map(type => {
        const input = element(); input.className = 'timeline-type'; input.value = type; input.checked = true;
        filterIds['timeline-search'].appendChild(input);
        return input;
    });
    const filterRequests = [];
    const filterStorage = new Map();
    const filterContext = {
        document: {body: element(), createElement: () => element(), getElementById: id => filterIds[id]},
        sessionStorage: {getItem: key => filterStorage.get(key), setItem: (key, value) => filterStorage.set(key, value), removeItem: key => filterStorage.delete(key)},
        window: {axios: {post(url, data) { return new Promise((resolve, reject) => filterRequests.push({data, resolve, reject})); }}}
    };
    const source = fs.readFileSync('resources/js/views/timeline-events-admin.js', 'utf8');
    vm.runInNewContext(source, filterContext);
    filterIds['reference-year'].value = '1800';
    const filteredSubmit = () => filterIds['timeline-search'].events.submit({preventDefault() {}});
    const payloadTypes = index => Array.from(filterRequests[index].data.types);
    filteredSubmit();
    assert.deepStrictEqual(payloadTypes(0), types, 'All subject choices are sent by default');
    assert.ok(inputs.every(input => input.disabled), 'Choices cannot change during discovery');
    filterRequests[0].resolve({data: {search_id: 'all-search', count: 10, has_more: true, start_year: 1800, end_year: 1810, html: 'all results'}});
    await settle();
    assert.ok(inputs.every(input => !input.disabled));
    filterIds['timeline-more'].events.click();
    assert.deepStrictEqual(payloadTypes(1), types, 'More retains the chosen types');
    assert.strictEqual(filterRequests[1].data.search_id, 'all-search');
    filterRequests[1].reject({response: {status: 503, data: {message: 'Please retry.'}}});
    await settle();
    assert.ok(inputs.every(input => !input.disabled), 'Failures restore all controls');
    inputs.forEach(input => { input.checked = input.value === 'science'; });
    inputs[0].events.change();
    assert.strictEqual(filterIds['timeline-candidates'].html, '');
    assert.strictEqual(filterIds['timeline-more'].hidden, true);
    assert.strictEqual(filterIds['timeline-results-heading'].hidden, true);
    filteredSubmit();
    assert.deepStrictEqual(payloadTypes(2), ['science']);
    assert.strictEqual(filterRequests[2].data.search_id, null, 'Changed choices use a fresh search');
    filterRequests[2].resolve({data: {search_id: 'science-search', count: 10, has_more: true, start_year: 1800, end_year: 1810, html: 'science results'}});
    await settle();
    assert.deepStrictEqual(JSON.parse(filterStorage.get('pianolit.timeline.library')).types, ['science']);
    vm.runInNewContext(source, filterContext); // A page reload restores its cursor and choices.
    assert.deepStrictEqual(inputs.filter(input => input.checked).map(input => input.value), ['science']);
    assert.strictEqual(filterIds['timeline-more'].hidden, true, 'Restored choices still need visible results before More');
    filteredSubmit();
    assert.strictEqual(filterRequests[3].data.search_id, 'science-search');
    assert.deepStrictEqual(payloadTypes(3), ['science']);
    filterRequests[3].resolve({data: {search_id: 'science-search', count: 1, has_more: false, html: 'one science event'}});
    await settle();
    inputs.forEach(input => { input.checked = false; });
    inputs[0].events.change();
    filteredSubmit();
    assert.strictEqual(filterRequests.length, 4, 'No selection never sends an API request');
    assert.strictEqual(filterIds['timeline-search-status'].textContent, 'Choose at least one event type.');
    assert.strictEqual(inputs[0].focused, true);
    assert.strictEqual(filterIds['timeline-find'].disabled, false);
    console.log('Passed: default/selected event types, filtered pagination, reset on changes, restored choices/cursors, empty selection and failure recovery.');

    const savedRoot = libraryIds['timeline-saved'];
    function savedRow(id, date) {
        const row = element();
        row.dataset = {eventId: String(id), year: date.slice(0, 4), sort: date};
        return row;
    }
    const saved1799 = savedRow(1, '1799-01-01');
    const saved1800 = savedRow(2, '1800-01-01');
    const saved1809 = savedRow(3, '1809-07-01');
    savedRoot.appendChild(saved1809);
    savedRoot.appendChild(saved1799);
    savedRoot.appendChild(saved1800);
    vm.runInNewContext(fs.readFileSync('resources/js/views/timeline-events-admin.js', 'utf8'), libraryContext);
    assert.deepStrictEqual(savedRoot.children.map(group => group.dataset.decade), ['1790', '1800']);
    const group1800 = savedRoot.children[1];
    assert.strictEqual(group1800.open, false, 'Saved decades start collapsed');
    assert.strictEqual(group1800.querySelector('.timeline-decade-count').textContent, '2 events');
    assert.deepStrictEqual(group1800.querySelectorAll('[data-event-id]').map(row => row.dataset.eventId), ['2', '3']);
    group1800.open = true;
    saved1800.open = true;
    const saved1805 = savedRow(4, '1805-01-01');
    const saved1810 = savedRow(5, '1810-01-01');
    savedRoot.insertAdjacentHTML = function (position, html) { this.appendChild(html === '1805' ? saved1805 : saved1810); };
    const decadeSaveButton = element();
    const decadeSaveStatus = element();
    const decadeCard = element();
    decadeCard.dataset.candidate = 'Q100:created:1805';
    decadeCard.querySelector = selector => selector === '.timeline-save' ? decadeSaveButton : decadeSaveStatus;
    decadeSaveButton.closest = () => decadeCard;
    const saveInDecade = () => libraryCandidateRoot.events.click({target: {closest: () => decadeSaveButton}});
    saveInDecade();
    libraryRequests[3].resolve({data: {id: 4, count: 4, html: '1805'}});
    await settle();
    assert.strictEqual(savedRoot.children[1], group1800, 'Saving reuses the existing decade');
    assert.strictEqual(group1800.open, true, 'Saving preserves an open decade');
    assert.strictEqual(saved1800.open, true, 'Saving preserves the existing event editor');
    assert.deepStrictEqual(group1800.querySelectorAll('[data-event-id]').map(row => row.dataset.eventId), ['2', '4', '3']);
    assert.strictEqual(group1800.querySelector('.timeline-decade-count').textContent, '3 events');
    decadeSaveButton.dataset.saved = 'false'; decadeSaveButton.disabled = false;
    saveInDecade();
    libraryRequests[4].resolve({data: {id: 5, count: 5, html: '1810'}});
    await settle();
    assert.deepStrictEqual(savedRoot.children.map(group => group.dataset.decade), ['1790', '1800', '1810']);
    assert.strictEqual(savedRoot.children[2].open, false, 'A newly saved decade starts collapsed');
    assert.strictEqual(savedRoot.children[2].querySelector('.timeline-decade-count').textContent, '1 event');
    const deletionRequests = [];
    libraryContext.window.axios.delete = () => new Promise((resolve, reject) => deletionRequests.push({resolve, reject}));
    function deleteRow(row) {
        const form = element(); const button = element(); const status = element();
        form.closest = selector => selector === '.timeline-remove' ? form : row;
        form.querySelector = selector => selector === 'button[type="submit"]' ? button : status;
        savedRoot.events.submit({target: form, preventDefault() {}});
    }
    deleteRow(saved1805);
    deletionRequests[0].reject({response: {status: 500}});
    await settle();
    assert.strictEqual(group1800.querySelector('.timeline-decade-count').textContent, '3 events', 'A failed removal preserves the decade count');
    deleteRow(saved1805);
    deletionRequests[1].resolve({data: {count: 4, source_id: 'other'}});
    await settle();
    assert.strictEqual(group1800.querySelector('.timeline-decade-count').textContent, '2 events');
    assert.strictEqual(group1800.open, true);
    deleteRow(saved1810);
    deletionRequests[2].resolve({data: {count: 3, source_id: 'other'}});
    await settle();
    assert.deepStrictEqual(savedRoot.children.map(group => group.dataset.decade), ['1790', '1800'], 'Removing the final event removes its empty decade');
    deleteRow(saved1799); deletionRequests[3].resolve({data: {count: 2, source_id: 'other'}}); await settle();
    deleteRow(saved1800); deletionRequests[4].resolve({data: {count: 1, source_id: 'other'}}); await settle();
    assert.strictEqual(group1800.querySelector('.timeline-decade-count').textContent, '1 event');
    deleteRow(saved1809); deletionRequests[5].resolve({data: {count: 0, source_id: 'other'}}); await settle();
    assert.strictEqual(savedRoot.children.length, 0);
    assert.strictEqual(libraryIds['timeline-empty'].hidden, false);
    assert.strictEqual(libraryIds['page-heading'].textContent, 'Timeline events');
    console.log('Passed: flat chronological library results, fixed title, restored search state, live saved/decade counts, pagination, AJAX removal/retry, CSRF, save races/idempotency, and expired sessions.');
}
main().catch(error => { console.error(error); process.exitCode = 1; });
