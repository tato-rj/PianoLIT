(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory;
    else factory(root);
})(typeof window === 'undefined' ? this : window, function (win) {
    'use strict';
    var doc = win.document;
    var current = null;
    var reducedMotion = win.matchMedia('(prefers-reduced-motion: reduce)');

    function saveStyles(element, properties) {
        var saved = properties.map(function (name) {
            return [name, element.style.getPropertyValue(name), element.style.getPropertyPriority(name)];
        });
        return function () {
            saved.forEach(function (property) {
                if (property[1]) element.style.setProperty(property[0], property[1], property[2]);
                else element.style.removeProperty(property[0]);
            });
        };
    }

    function stickyElements(content) {
        return Array.from(content.querySelectorAll('*')).filter(function (element) {
            return win.getComputedStyle(element).position === 'sticky' && element.getClientRects().length;
        }).map(function (element) {
            return {element: element, rect: element.getBoundingClientRect(), restore: saveStyles(element, ['position', 'top', 'right', 'bottom', 'left'])};
        });
    }

    function pinSticky(items) {
        items.forEach(function (item) {
            var style = item.element.style;
            style.setProperty('position', 'relative', 'important');
            style.setProperty('top', '0px', 'important');
            style.setProperty('left', '0px', 'important');
            style.setProperty('right', 'auto', 'important');
            style.setProperty('bottom', 'auto', 'important');
        });
        items.forEach(function (item) {
            var rect = item.element.getBoundingClientRect();
            item.element.style.setProperty('top', (item.rect.top - rect.top) + 'px', 'important');
            item.element.style.setProperty('left', (item.rect.left - rect.left) + 'px', 'important');
        });
    }

    function dimensions(state, width) {
        state.page.style.setProperty('width', width + 'px');
        // Account for the original scrollbar gutter without widening/reflowing the page.
        var gap = Math.max(0, win.innerWidth - width);
        var distance = Math.max(0, state.panel.getBoundingClientRect().width - gap);
        state.page.style.setProperty('--account-reveal-offset', distance + 'px');
        state.distance = distance;
        if (state.control) positionControl(state);
    }

    function positionControl(state) {
        var placeholder = state.control.placeholder.getBoundingClientRect();
        var page = state.page.getBoundingClientRect();
        var width = state.control.width;
        var left = placeholder.left - page.left;
        var strip = Math.max(width + 4, page.width - state.distance);
        var destination = Math.max(2, strip - width - 16);
        var top = placeholder.top;
        var destinationTop = Math.max(2, Math.min(16, win.innerHeight - state.control.height - 2));
        state.control.host.style.setProperty('left', left + 'px');
        state.control.host.style.setProperty('top', top + 'px');
        state.control.host.style.setProperty('--account-toggle-offset', (destination - left) + 'px');
        state.control.host.style.setProperty('--account-toggle-offset-y', (destinationTop - top) + 'px');
    }

    function prepareControl(state) {
        var button = doc.querySelector('[data-account-navigation-toggle]');
        if (!button) return;
        var rect = button.getBoundingClientRect();
        var attributes = ['aria-label', 'title', 'aria-haspopup', 'data-bs-toggle', 'data-bs-dismiss'].map(function (name) {
            return [name, button.getAttribute(name)];
        });
        var placeholder = doc.createElement('span');
        placeholder.className = 'account-reveal-toggle-placeholder';
        placeholder.setAttribute('aria-hidden', 'true');
        placeholder.style.setProperty('width', rect.width + 'px');
        placeholder.style.setProperty('height', rect.height + 'px');
        button.parentNode.insertBefore(placeholder, button);
        var host = doc.createElement('div');
        host.className = 'account-reveal-toggle';
        doc.body.appendChild(host);
        host.appendChild(button);
        var owns = state.panel.getAttribute('aria-owns');
        if (button.id) state.panel.setAttribute('aria-owns', ((owns || '') + ' ' + button.id).trim());
        button.setAttribute('aria-label', 'Close account menu');
        button.setAttribute('title', 'Close account menu');
        button.removeAttribute('aria-haspopup');
        function allowOutsideFocus(event) { event.stopPropagation(); }
        button.addEventListener('focusin', allowOutsideFocus);
        state.control = {
            button: button, placeholder: placeholder, host: host, width: rect.width, height: rect.height,
            restore: function () {
                button.removeEventListener('focusin', allowOutsideFocus);
                button.classList.remove('account-circle--close');
                attributes.forEach(function (attribute) {
                    if (attribute[1] === null) button.removeAttribute(attribute[0]);
                    else button.setAttribute(attribute[0], attribute[1]);
                });
                if (owns === null) state.panel.removeAttribute('aria-owns');
                else state.panel.setAttribute('aria-owns', owns);
                if (placeholder.parentNode) placeholder.parentNode.replaceChild(button, placeholder);
                host.remove();
            }
        };
        positionControl(state);
    }

    function reveal(state, opened) {
        state.page.classList.toggle('account-page--revealed', opened);
        if (state.control) {
            state.control.host.classList.toggle('account-reveal-toggle--revealed', opened);
            state.control.button.classList.toggle('account-circle--close', opened);
        }
    }

    function restore(state, returnFocus) {
        if (current !== state) return;
        current = null;
        if (state.frame !== null) win.cancelAnimationFrame(state.frame);
        if (state.dismissTimer !== null) win.clearTimeout(state.dismissTimer);
        state.sticky.forEach(function (item) { item.restore(); });
        state.page.classList.remove('account-page--revealed', 'account-page--prepared');
        state.page.inert = state.inert;
        if (state.control) state.control.restore();
        state.restorePage();
        state.restoreBody();
        if (state.backdrop) state.backdrop.classList.remove('account-reveal-backdrop');
        win.scrollTo(state.x, state.y);
        if (returnFocus && !doc.querySelector('.modal.show, .offcanvas.show, .offcanvas.showing')
            && state.opener && state.opener.getClientRects().length) {
            state.opener.focus({preventScroll: true});
        }
    }

    function show(event) {
        if (event.defaultPrevented || !event.target.matches('[data-account-reveal]') || current) return;
        var page = doc.querySelector('[data-account-page]');
        var content = page && page.querySelector('[data-account-page-content]');
        if (!content) return; // Other layouts retain the ordinary Bootstrap overlay.
        var width = page.getBoundingClientRect().width;
        var height = Math.max(doc.documentElement.scrollHeight, doc.body.scrollHeight);
        var state = {
            page: page, panel: event.target, opener: event.relatedTarget || doc.activeElement,
            x: win.scrollX, y: win.scrollY, inert: page.inert,
            sticky: stickyElements(content), frame: null, dismissTimer: null, backdrop: null,
            restorePage: saveStyles(page, ['width', '--account-page-scroll-x', '--account-page-scroll-y', '--account-reveal-offset']),
            restoreBody: saveStyles(doc.body, ['min-height'])
        };
        var previousBackdrops = Array.from(state.panel.parentElement.querySelectorAll('.offcanvas-backdrop'));
        current = state;
        // A viewport frame keeps fixed players/navigation anchored; the inner page
        // retains its scroll offset. Existing media nodes are never reparented.
        doc.body.style.setProperty('min-height', height + 'px');
        page.style.setProperty('--account-page-scroll-x', -state.x + 'px');
        page.style.setProperty('--account-page-scroll-y', -state.y + 'px');
        dimensions(state, width);
        page.classList.add('account-page--prepared');
        pinSticky(state.sticky);
        prepareControl(state);
        page.inert = true;
        void page.offsetWidth;
        Promise.resolve().then(function () {
            if (current !== state) return;
            if (event.defaultPrevented) return restore(state, true);
            // Finish the opening click before changing its delegated action;
            // otherwise Bootstrap could also dismiss it during that same click.
            if (state.control) {
                state.dismissTimer = win.setTimeout(function () {
                    state.dismissTimer = null;
                    if (current !== state) return;
                    state.control.button.removeAttribute('data-bs-toggle');
                    state.control.button.setAttribute('data-bs-dismiss', 'offcanvas');
                }, 0);
            }
            var backdrops = Array.from(state.panel.parentElement.querySelectorAll('.offcanvas-backdrop'));
            state.backdrop = backdrops.filter(function (item) { return previousBackdrops.indexOf(item) === -1; }).pop();
            if (state.backdrop) state.backdrop.classList.add('account-reveal-backdrop');
            if (reducedMotion.matches) reveal(state, true);
            else state.frame = win.requestAnimationFrame(function () {
                state.frame = null;
                if (current === state && !state.closing) reveal(state, true);
            });
        });
    }

    function hide(event) {
        if (event.defaultPrevented || !current || event.target !== current.panel) return;
        var state = current;
        state.closing = true;
        reveal(state, false);
        Promise.resolve().then(function () {
            if (current === state && event.defaultPrevented) {
                state.closing = false;
                reveal(state, true);
            }
        });
    }

    function hidden(event) {
        if (current && event.target === current.panel) restore(current, true);
    }

    function resize() {
        if (!current) return;
        var body = win.getComputedStyle(doc.body);
        var width = doc.body.clientWidth - parseFloat(body.paddingLeft || 0) - parseFloat(body.paddingRight || 0);
        dimensions(current, width);
    }

    function keyboard(event) {
        var state = current;
        if (!state || !state.control || state.closing) return;
        var button = state.control.button;
        var active = doc.activeElement;
        if (event.key === 'Escape' && active === button) {
            event.preventDefault();
            event.stopPropagation();
            button.click();
            return;
        }
        if (event.key !== 'Tab') return;
        var items = Array.from(state.panel.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), summary, [tabindex]:not([tabindex="-1"])'))
            .filter(function (item) { return item.getClientRects().length && win.getComputedStyle(item).visibility !== 'hidden'; });
        var first = items[0], last = items[items.length - 1];
        var next = null;
        if (active === button) next = event.shiftKey ? last : first;
        else if (active === state.panel) next = event.shiftKey ? last : button;
        else if ((active === first && event.shiftKey) || (active === last && !event.shiftKey)) next = button;
        if (next) {
            event.preventDefault();
            event.stopPropagation();
            next.focus();
        }
    }

    doc.addEventListener('show.bs.offcanvas', show);
    doc.addEventListener('hide.bs.offcanvas', hide);
    // Restore before Bootstrap's focus-return and queued overlay handoff handlers.
    doc.addEventListener('hidden.bs.offcanvas', hidden, true);
    doc.addEventListener('keydown', keyboard, true);
    win.addEventListener('resize', resize, {passive: true});
});
