(function () {
    'use strict';

    function install(doc, view) {
        function showLatest() {
            var charts = doc.querySelectorAll('[data-daily-activity-scroll]');
            charts.forEach(function (chart) {
                chart.scrollLeft = Math.max(0, chart.scrollWidth - chart.clientWidth);
            });
        }

        if (doc.readyState === 'loading') doc.addEventListener('DOMContentLoaded', showLatest, {once: true});
        else showLatest();
        view.addEventListener('pageshow', function (event) {
            var navigation = view.performance.getEntriesByType('navigation')[0];
            if (event.persisted || (navigation && (navigation.type === 'back_forward' || navigation.type === 'reload'))) {
                view.requestAnimationFrame(showLatest);
            }
        });
    }

    if (typeof module !== 'undefined') module.exports = {install: install};
    if (typeof document !== 'undefined') install(document, window);
})();
