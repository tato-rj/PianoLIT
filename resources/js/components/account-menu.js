(function () {
    'use strict';
    var active = null;

    function navigationExpanded(event, expanded) {
        if (!event.target.matches('.account-navigation')) return;
        document.querySelectorAll('[data-account-navigation-toggle]').forEach(function (trigger) {
            if (trigger.getAttribute('aria-controls') === event.target.id) {
                trigger.setAttribute('aria-expanded', String(expanded));
            }
        });
    }

    document.addEventListener('shown.bs.offcanvas', function (event) { navigationExpanded(event, true); });
    document.addEventListener('hidden.bs.offcanvas', function (event) { navigationExpanded(event, false); });

    function position(trigger, menu) {
        var button = trigger.getBoundingClientRect();
        var styles = window.getComputedStyle(menu);
        var inset = parseFloat(styles.paddingTop) + parseFloat(styles.borderTopWidth);
        // Layout width stays stable while the opening scale animation runs.
        var width = menu.offsetWidth;
        var left = Math.max(8, Math.min(button.right + inset - width, document.documentElement.clientWidth - width - 8));
        var available = button.left - left - inset - 8;
        var stacked = available < 140;
        menu.style.setProperty('--account-menu-left', left + 'px');
        menu.style.setProperty('--account-menu-top', (button.top - inset) + 'px');
        menu.style.setProperty('--account-menu-origin-x', (button.left + button.width / 2 - left) + 'px');
        menu.style.setProperty('--account-menu-origin-y', (inset + button.height / 2) + 'px');
        menu.style.setProperty('--account-menu-details-width', stacked ? '100%' : available + 'px');
        menu.classList.toggle('account-menu--stacked', stacked);
    }

    document.addEventListener('shown.bs.dropdown', function (event) {
        var trigger = event.target;
        if (!trigger.matches('[data-account-menu-toggle]')) return;
        var menu = trigger.parentElement.querySelector('.account-menu');
        if (!menu) return;
        menu.classList.add('account-menu--anchored');
        active = {trigger: trigger, menu: menu};
        position(trigger, menu);
    });

    document.addEventListener('hidden.bs.dropdown', function (event) {
        if (active && active.trigger === event.target) active = null;
    });

    window.addEventListener('resize', function () {
        if (active) position(active.trigger, active.menu);
    });

    // Dismiss on page scrolling so the floating menu never detaches from its circle.
    document.addEventListener('scroll', function (event) {
        if (!active || active.menu.contains(event.target)) return;
        window.bootstrap.Dropdown.getInstance(active.trigger).hide();
    }, {passive: true, capture: true});
})();
