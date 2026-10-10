const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

module.exports = async function () {
    const template = fs.readFileSync(path.join(__dirname, '../../resources/views/webapp/search/index.blade.php'), 'utf8');
    const loadResults = template.slice(template.indexOf('function loadResults()'), template.indexOf('</script>', template.indexOf('function loadResults()')));
    const helpers = template.slice(template.indexOf('function makeUrl()'), template.indexOf('</script>', template.indexOf('function makeUrl()')));
    const settle = async () => { for (let i = 0; i < 8; i++) await Promise.resolve(); };
    for (const guest of [true, false]) {
        const pending = [];
        const elements = {};
        const $ = selector => elements[selector] || (elements[selector] = {
            html: '', visible: false, enabled: true,
            append(value) { this.html += value; return this; },
            empty() { this.html = ''; return this; },
            text(value) { if (!arguments.length) return this.html; this.html = value; return this; },
            show() { this.visible = true; return this; },
            hide() { this.visible = false; return this; },
            enable() { this.enabled = true; return this; },
            disable() { this.enabled = false; return this; },
            length: 3,
        });
        const context = {
            $, URL, window: {page: 1, loading: false, done: false, filters: [], searchRequestId: 0, location: {href: 'https://my.example.test/search?search=happy&page=99#old'}},
            axios: {get(url, options) { return new Promise((resolve, reject) => pending.push({url, options, resolve, reject})); }},
        };
        vm.runInNewContext(loadResults.replace(/\{\{.*?\}\}/g, String(guest)) + helpers, context);
        context.loadResults();
        context.loadResults();
        assert.strictEqual(pending.length, 1, 'Scroll while loading must not duplicate requests');
        assert.strictEqual(new URL(pending[0].url).searchParams.get('page'), '1');
        assert.strictEqual(new URL(pending[0].url).searchParams.get('include_total'), '1');
        pending[0].resolve({data: '<article>first results</article>', headers: {'x-search-total': '24'}});
        await settle();
        assert.strictEqual(context.window.done, guest);
        assert.strictEqual(context.window.loading, false);
        assert.strictEqual($('#search-results-count').html, '24 results');
        assert.strictEqual($('#search-results-count').visible, true);
        if (guest) {
            context.loadResults();
            assert.strictEqual(pending.length, 1, 'Visitor scrolling must stop after the first response');
        }
        context.applyFilters(['new filter']);
        context.applyFilters(['latest filter']);
        const stale = pending[pending.length - 2];
        const latest = pending[pending.length - 1];
        latest.resolve({data: '<article>latest results</article>', headers: {'x-search-total': '1'}});
        await settle();
        stale.resolve({data: '<article>stale results</article>', headers: {'x-search-total': '99'}});
        await settle();
        assert.strictEqual($('#pieces-list').html.includes('stale results'), false);
        assert.strictEqual($('#search-results-count').html, '1 result', 'A stale response cannot replace the current total');
        assert.strictEqual(context.window.loading, false);
        if (!guest) {
            context.loadResults();
            pending[pending.length - 1].resolve({data: ''});
            await settle();
            assert.strictEqual($('#empty').visible, false, 'End of pagination is not an empty search');
            assert.strictEqual($('#search-results-count').html, '1 result');
            assert.strictEqual($('#search-feedback').visible, false, 'There is no total message at the bottom');
        }
        context.reset();
        assert.strictEqual($('#search-results-count').visible, false, 'New searches hide the previous total');
        assert.strictEqual($('#search-results-count').html, '');
        context.applyFilters([]);
        pending[pending.length - 1].reject(new Error('offline'));
        await settle();
        assert.strictEqual(context.window.loading, false);
        assert.strictEqual($('#spinner').visible, false);
        assert.strictEqual($('#empty').visible, false, 'Network errors must not show the no-matches illustration');
        assert.strictEqual($('#search-feedback').visible, true);
        assert.strictEqual($('#options button, .options-columns input').enabled, true);
        context.loadResults();
        pending[pending.length - 1].resolve({data: ' \n ', headers: {'x-search-total': '0'}});
        await settle();
        assert.strictEqual(context.window.done, true);
        assert.strictEqual($('#empty').visible, true);
        assert.strictEqual($('#search-results-count').html, '0 results');
        assert.strictEqual($('#search-feedback').visible, false);
        assert.strictEqual($('#empty [data-empty-message]').html, 'We couldn’t find any pieces matching “happy”.');
        context.reset();
        context.window.location.href = 'https://my.example.test/search?catalogue=1';
        context.applyFilters([]);
        pending[pending.length - 1].resolve({data: ''});
        await settle();
        assert.strictEqual($('#empty [data-empty-message]').html, 'We couldn’t find any pieces matching your filters.');
        if (!guest) {
            context.reset(); context.applyFilters([]);
            pending[pending.length - 1].resolve({data: '<article>legacy results</article>'});
            await settle(); context.loadResults();
            pending[pending.length - 1].resolve({data: ''}); await settle();
            assert.strictEqual($('#search-results-count').html, '3 results', 'Header-less responses still move the final count to the top');
            assert.strictEqual($('#search-feedback').visible, false);
        }
    }
    const countMarkup = template.indexOf('id="search-results-count"');
    assert(countMarkup > template.indexOf("@include('webapp.search.form')") && countMarkup < template.indexOf('id="pieces-list"'));
    assert(!template.includes('We found a total of'));
    console.log('Passed: webapp visitor search stop, duplicate requests, filter races, retry and empty results.');
};
