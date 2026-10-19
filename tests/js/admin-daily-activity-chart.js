const assert = require('assert');
const {install} = require('../../resources/js/components/admin-daily-activity-chart');

module.exports = function () {
    function fixture(small, loading = false) {
        const documentEvents = {}, windowEvents = {}, mediaEvents = {};
        const media = {matches: small, addEventListener(name, handler) { mediaEvents[name] = handler; }};
        let position = 0;
        const chart = {dataset: {}, scrollWidth: 1200, clientWidth: 250,
            get scrollLeft() { return position; }, set scrollLeft(value) { position = Math.max(0, Math.min(950, value)); }};
        const fitting = {dataset: {}, scrollWidth: 200, clientWidth: 200, scrollLeft: 0};
        const doc = {readyState: loading ? 'loading' : 'complete', querySelectorAll() { return [chart, fitting]; }, addEventListener(name, handler) { documentEvents[name] = handler; }};
        const view = {matchMedia() { return media; }, addEventListener(name, handler) { windowEvents[name] = handler; }, requestAnimationFrame(handler) { handler(); }};
        install(doc, view);
        return {chart, fitting, media, documentEvents, windowEvents, mediaEvents};
    }

    const phone = fixture(true);
    assert.strictEqual(phone.chart.scrollLeft, 950, 'A small screen starts at the latest dates.');
    assert.strictEqual(phone.fitting.scrollLeft, 0, 'A chart that fits does not move.');
    phone.chart.scrollLeft = 100;
    phone.mediaEvents.change();
    phone.windowEvents.pageshow({persisted: false});
    assert.strictEqual(phone.chart.scrollLeft, 100, 'Later lifecycle events preserve manual exploration.');
    phone.windowEvents.pageshow({persisted: true});
    assert.strictEqual(phone.chart.scrollLeft, 950, 'Returning to a cached page starts with the latest dates.');

    const desktop = fixture(false);
    assert.strictEqual(desktop.chart.scrollLeft, 0, 'Desktop keeps its existing initial position.');
    desktop.media.matches = true; desktop.mediaEvents.change();
    assert.strictEqual(desktop.chart.scrollLeft, 950, 'Entering the small-screen layout initializes the chart once.');
    desktop.chart.scrollLeft = 200;
    desktop.media.matches = false; desktop.mediaEvents.change();
    desktop.media.matches = true; desktop.mediaEvents.change();
    assert.strictEqual(desktop.chart.scrollLeft, 200, 'Resizing again does not override the chosen position.');

    const loading = fixture(true, true);
    assert.strictEqual(loading.chart.scrollLeft, 0);
    loading.documentEvents.DOMContentLoaded();
    assert.strictEqual(loading.chart.scrollLeft, 950, 'Wait for the chart to be laid out during page parsing.');
};
