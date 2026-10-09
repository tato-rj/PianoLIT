const assert = require('assert');
const mount = require('../../resources/js/views/highlights');
module.exports = async function () {
    function node() {
        return {hidden: true, events: {}, attrs: {}, children: [], innerHTML: 'initial cards',
            getAttribute() { return '/highlights?explore%5Bmood%5D=dramatic'; },
            setAttribute(key, value) { this.attrs[key] = value; },
            addEventListener(event, fn) { this.events[event] = fn; }};
    }
    const ids = {};
    ['pieces-list', 'highlights-loading', 'highlights-empty', 'highlights-error', 'server-filter', 'highlights-retry'].forEach(id => ids[id] = node());
    let selected = ['baroque'], sort = null, sorts = 0;
    ids['server-filter'].querySelectorAll = () => [{querySelectorAll: () => selected.map(value => ({value}))}];
    const requests = [], events = {};
    mount({document: {getElementById: id => ids[id], querySelector: () => sort},
        addEventListener: (name, fn) => events[name] = fn,
        jQuery: () => ({sortChildrenBy: () => sorts++}),
        axios: {CancelToken: {source: () => { const token = {}; return {token, cancel: () => token.cancelled = true}; }},
            isCancel: reason => !!reason.cancelled,
            get: (url, options) => new Promise((resolve, reject) => requests.push({url, options, resolve, reject}))}
    });
    const flush = async () => { for (let i = 0; i < 5; i++) await Promise.resolve(); };
    const change = () => ids['server-filter'].events.change({target: {type: 'checkbox'}});
    assert.strictEqual(requests.length, 0, 'Initial cards need no duplicate AJAX request');
    change(); selected = ['elementary']; change();
    assert(requests[0].options.cancelToken.cancelled);
    assert.strictEqual(requests[1].url, '/highlights?explore%5Bmood%5D=dramatic', 'Filtering preserves the Explore context in the endpoint');
    assert.deepStrictEqual(requests[1].options.params.filters, ['["elementary"]']);
    requests[0].resolve({data: 'stale'}); await flush();
    assert.strictEqual(ids['pieces-list'].innerHTML, '');
    assert.strictEqual(ids['highlights-loading'].hidden, false, 'A stale response cannot stop the active spinner');
    sort = {}; ids['pieces-list'].children = [{}];
    requests[1].resolve({data: 'current cards'}); await flush();
    assert.strictEqual(ids['pieces-list'].innerHTML, 'current cards');
    assert.strictEqual(sorts, 1, 'The active sort is reapplied to all filtered results');
    assert.strictEqual(ids['highlights-loading'].hidden, true);
    assert.strictEqual(ids['pieces-list'].attrs['aria-busy'], 'false');
    change(); requests[2].reject(new Error('timeout')); await flush();
    assert.strictEqual(ids['highlights-error'].hidden, false);
    assert.strictEqual(ids['highlights-loading'].hidden, true);
    ids['highlights-retry'].events.click();
    ids['pieces-list'].children = []; requests[3].resolve({data: ''}); await flush();
    assert.strictEqual(ids['highlights-empty'].hidden, false);
    assert.strictEqual(ids['highlights-error'].hidden, true);
    change(); events.pagehide(); events.pageshow();
    assert(requests[4].options.cancelToken.cancelled);
    requests[4].resolve({data: 'abandoned'}); await flush();
    assert.strictEqual(ids['pieces-list'].innerHTML, '');
    requests[5].resolve({data: 'restored'}); await flush();
    assert.strictEqual(ids['pieces-list'].innerHTML, 'restored');
    console.log('Passed: highlights initial rendering, filter cancellation/stale responses, sorting, timeout/retry, empty results and page-cache recovery.');
};
