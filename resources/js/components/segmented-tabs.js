// Bootstrap owns selection; one flat white background moves behind its links.
module.exports = function initializeSlidingTabs(win) {
    const doc = win.document;

    function update(nav, animate) {
        const selected = nav.querySelector('.nav-link.active');
        const background = nav.querySelector('.tab-navigation__selection');
        if (!selected || !background || !nav.clientWidth) return;
        const parent = nav.getBoundingClientRect();
        const bounds = selected.getBoundingClientRect();
        const snap = !animate || !nav.hasAttribute('data-sliding-tabs-ready');
        if (snap) background.style.transition = 'none';
        background.style.width = bounds.width + 'px';
        background.style.height = bounds.height + 'px';
        background.style.transform = 'translate(' + (bounds.left - parent.left + nav.scrollLeft - nav.clientLeft) + 'px, ' + (bounds.top - parent.top + nav.scrollTop - nav.clientTop) + 'px)';
        nav.setAttribute('data-sliding-tabs-ready', '');
        if (snap) {
            void background.offsetWidth;
            background.style.removeProperty('transition');
        }
    }

    doc.addEventListener('shown.bs.tab', function (event) {
        const nav = event.target.closest('[data-sliding-tabs]');
        if (nav) update(nav, true);
    });

    function start() {
        doc.querySelectorAll('[data-sliding-tabs]').forEach(function (nav) {
            update(nav, false);
            if (win.ResizeObserver) {
                const observer = new win.ResizeObserver(function () { update(nav, false); });
                observer.observe(nav);
                nav.querySelectorAll('.nav-link').forEach(function (link) { observer.observe(link); });
            } else {
                win.addEventListener('resize', function () { update(nav, false); });
            }
        });
    }

    if (doc.readyState === 'loading') doc.addEventListener('DOMContentLoaded', start, {once: true});
    else start();
};
