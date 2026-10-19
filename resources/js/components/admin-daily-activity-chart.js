(function () {
    'use strict';

    function install(doc, view) {
        var charts = Array.prototype.slice.call(doc.querySelectorAll('[data-daily-activity-scroll]'));
        if (!charts.length) return;
        var smallScreen = view.matchMedia('(max-width: 700px)');

        function showLatest() {
            if (!smallScreen.matches) return;
            charts.forEach(function (chart) {
                if (chart.dataset.latestDatesShown || !chart.clientWidth) return;
                chart.scrollLeft = Math.max(0, chart.scrollWidth - chart.clientWidth);
                chart.dataset.latestDatesShown = 'true';
            });
        }

        if (doc.readyState === 'loading') doc.addEventListener('DOMContentLoaded', showLatest, {once: true});
        else showLatest();
        if (smallScreen.addEventListener) smallScreen.addEventListener('change', showLatest);
        else smallScreen.addListener(showLatest);
        view.addEventListener('pageshow', function (event) {
            if (!event.persisted) return;
            charts.forEach(function (chart) { delete chart.dataset.latestDatesShown; });
            view.requestAnimationFrame(showLatest);
        });
    }

    if (typeof module !== 'undefined') module.exports = {install: install};
    if (typeof document !== 'undefined') install(document, window);
})();
