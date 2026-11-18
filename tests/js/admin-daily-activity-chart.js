const assert = require('assert');
const {install} = require('../../resources/js/components/admin-daily-activity-chart');

module.exports = function () {
    function fixture(clientWidth, loading = false) {
        const documentEvents = {}, windowEvents = {};
        const navigation = {type: 'navigate'};
        const chart = {scrollWidth: 1200, clientWidth, scrollLeft: 0};
        const fitting = {scrollWidth: 200, clientWidth: 200, scrollLeft: 0};
        const doc = {readyState: loading ? 'loading' : 'complete', querySelectorAll() { return [chart, fitting]; }, addEventListener(name, handler) { documentEvents[name] = handler; }};
        const view = {addEventListener(name, handler) { windowEvents[name] = handler; },
            requestAnimationFrame(handler) { handler(); }, performance: {getEntriesByType() { return [navigation]; }}};
        install(doc, view);
        return {chart, fitting, navigation, documentEvents, windowEvents};
    }

    const phone = fixture(250);
    assert.strictEqual(phone.chart.scrollLeft, 950, 'A small screen starts at the latest dates.');
    assert.strictEqual(phone.fitting.scrollLeft, 0, 'A chart that fits does not move.');
    phone.chart.scrollLeft = 100;
    phone.windowEvents.pageshow({persisted: false});
    assert.strictEqual(phone.chart.scrollLeft, 100, 'Later lifecycle events preserve manual exploration.');
    phone.windowEvents.pageshow({persisted: true});
    assert.strictEqual(phone.chart.scrollLeft, 950, 'Returning to a cached page starts with the latest dates.');

    const desktop = fixture(800);
    assert.strictEqual(desktop.chart.scrollLeft, 400, 'A desktop chart also starts at the latest dates.');
    for (const type of ['back_forward', 'reload']) {
        desktop.navigation.type = type;
        desktop.chart.scrollLeft = 200;
        desktop.windowEvents.pageshow({persisted: false});
        assert.strictEqual(desktop.chart.scrollLeft, 400, 'The latest dates take precedence over restored scroll position.');
    }

    const loading = fixture(250, true);
    assert.strictEqual(loading.chart.scrollLeft, 0);
    loading.documentEvents.DOMContentLoaded();
    assert.strictEqual(loading.chart.scrollLeft, 950, 'Wait for the chart to be laid out during page parsing.');
};
