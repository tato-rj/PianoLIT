const assert = require('assert');
const install = require('../../resources/js/components/admin-page-loading');

module.exports = async function () {
    function fixture({complete = false, native = true} = {}) {
        const events = {}, documentEvents = {}, navigationEvents = {}, timers = new Map(), intervals = new Map();
        let next = 0;
        const classes = new Set();
        const bar = {style: {}};
        const indicator = {hidden: true, classList: {contains: value => classes.has(value), add: value => classes.add(value), remove: value => classes.delete(value)}, querySelector: () => bar};
        const win = {
            document: {readyState: complete ? 'complete' : 'loading', querySelector: () => indicator, addEventListener: (name, fn) => { documentEvents[name] = fn; }},
            addEventListener: (name, fn) => { events[name] = fn; },
            setInterval(fn) { intervals.set(++next, fn); return next; }, clearInterval(id) { intervals.delete(id); },
            setTimeout(fn) { timers.set(++next, fn); return next; }, clearTimeout(id) { timers.delete(id); }
        };
        if (native) win.navigation = {addEventListener: (name, fn) => { navigationEvents[name] = fn; }};
        install(win);
        function flush() { const callbacks = [...timers.values()]; timers.clear(); callbacks.forEach(fn => fn()); }
        function navigate(properties = {}) {
            const event = {destination: {sameDocument: false}, hashChange: false, downloadRequest: null, defaultPrevented: false, signal: {aborted: false, addEventListener(name, fn) { this[name] = fn; }}, ...properties};
            navigationEvents.navigate(event);
            return event;
        }
        return {win, indicator, bar, events, documentEvents, navigationEvents, intervals, flush, navigate};
    }
    const initial = fixture();
    assert.strictEqual(initial.indicator.hidden, false, 'The initial response starts before the main bundle.');
    for (let n = 0; n < 100; n++) [...initial.intervals.values()].forEach(fn => fn());
    assert(parseFloat(initial.bar.style.transform.slice(7)) < 0.9, 'Estimated activity never claims completion.');
    initial.events.load();
    assert.strictEqual(initial.bar.style.transform, 'scaleX(1)');
    initial.flush(); initial.flush();
    assert.strictEqual(initial.indicator.hidden, true);
    assert.strictEqual(initial.intervals.size, 0);

    const page = fixture({complete: true});
    assert.strictEqual(page.indicator.hidden, true, 'Already loaded pages stay quiet.');
    for (const properties of [{destination: {sameDocument: true}}, {hashChange: true}, {downloadRequest: ''}, {defaultPrevented: true}, {signal: {aborted: true}}]) {
        page.navigate(properties); await Promise.resolve();
        assert.strictEqual(page.indicator.hidden, true, 'Anchors, downloads and canceled navigation stay quiet.');
    }
    const first = page.navigate(); await Promise.resolve();
    assert.strictEqual(page.indicator.hidden, false, 'A real link, refresh, form or history navigation starts the bar.');
    page.events.load();
    assert.notStrictEqual(page.bar.style.transform, 'scaleX(1)', 'An outgoing navigation cannot finish on the source page load.');
    const second = page.navigate(); await Promise.resolve();
    first.signal.abort();
    assert.notStrictEqual(page.bar.style.transform, 'scaleX(1)', 'An older cancellation cannot finish the newer navigation.');
    second.signal.abort();
    assert.strictEqual(page.bar.style.transform, 'scaleX(1)');
    // Start again during the finish animation: old hide timers must be canceled.
    page.navigate(); await Promise.resolve();
    page.flush(); page.flush();
    assert.strictEqual(page.indicator.hidden, false);
    page.events.pageshow({persisted: true}); page.flush(); page.flush();
    assert.strictEqual(page.indicator.hidden, true, 'Back/forward cache restoration clears the outgoing indicator.');
    page.navigate(); await Promise.resolve(); page.navigationEvents.navigateerror(); page.flush(); page.flush();
    assert.strictEqual(page.indicator.hidden, true, 'Navigation failure resets the indicator.');

    const fallback = fixture({complete: true, native: false});
    fallback.events.beforeunload({defaultPrevented: true, returnValue: ''}); await Promise.resolve();
    assert.strictEqual(fallback.indicator.hidden, true, 'Unsaved changes keep their existing confirmation behavior.');
    fallback.events.beforeunload({defaultPrevented: false, returnValue: ''}); await Promise.resolve();
    assert.strictEqual(fallback.indicator.hidden, false, 'Older browsers report native page departures.');
    fallback.documentEvents.keydown({key: 'Escape'}); fallback.flush(); fallback.flush();
    assert.strictEqual(fallback.indicator.hidden, true);
};
