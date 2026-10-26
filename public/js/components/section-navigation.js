(function () {
    'use strict';
    document.querySelectorAll('[data-section-navigation]').forEach(function (nav) {
        var sections = Array.from(nav.querySelectorAll('a[href^="#"]')).map(function (link) {
            return {link: link, section: document.getElementById(link.getAttribute('href').slice(1))};
        }).filter(function (item) { return item.section; });
        if (!sections.length) return;

        var activeLink = null;
        var scheduled = false;
        function updateLocation() {
            var line = Math.min(240, window.innerHeight * 0.3);
            var current = sections[0].link;
            sections.forEach(function (item) {
                if (item.section.getBoundingClientRect().top <= line) current = item.link;
            });
            // A short final section may never reach the activation line.
            var pageHeight = document.documentElement.scrollHeight;
            if (pageHeight > window.innerHeight && window.scrollY + window.innerHeight >= pageHeight - 2) {
                current = sections[sections.length - 1].link;
            }
            if (current === activeLink) return;
            sections.forEach(function (item) {
                if (item.link === current) item.link.setAttribute('aria-current', 'location');
                else item.link.removeAttribute('aria-current');
            });
            activeLink = current;
        }
        function scheduleLocation() {
            if (scheduled) return;
            scheduled = true;
            window.requestAnimationFrame(function () {
                scheduled = false;
                updateLocation();
            });
        }
        window.addEventListener('scroll', scheduleLocation, {passive: true});
        window.addEventListener('resize', scheduleLocation);
        window.addEventListener('load', scheduleLocation);
        window.addEventListener('pageshow', scheduleLocation);
        document.addEventListener('shown.bs.collapse', scheduleLocation);
        document.addEventListener('hidden.bs.collapse', scheduleLocation);
        updateLocation();
    });
}());
