const assert = require('assert');
const initialize = require('../../resources/js/components/account-reveal');

module.exports = async function () {
    function fixture(reduced = false, withControl = false) {
        const handlers = {}, frames = new Map(), timers = new Map();
        let frameId = 0;
        function style(initial = {}) {
            const values = {...initial}, priorities = {};
            return {
                getPropertyValue(name) { return values[name] || ''; },
                getPropertyPriority(name) { return priorities[name] || ''; },
                setProperty(name, value, priority = '') { values[name] = value; priorities[name] = priority; },
                removeProperty(name) { delete values[name]; delete priorities[name]; }
            };
        }
        function classes() {
            const values = new Set();
            return {add(...names) { names.forEach(n => values.add(n)); }, remove(...names) { names.forEach(n => values.delete(n)); }, contains(n) { return values.has(n); }, toggle(n, enabled) { if (enabled) values.add(n); else values.delete(n); }};
        }
        function node() {
            const attrs = {}, events = {};
            return {style: style(), classList: classes(), children: [], parentNode: null,
                getAttribute(n) { return Object.prototype.hasOwnProperty.call(attrs, n) ? attrs[n] : null; },
                setAttribute(n, v) { attrs[n] = v; }, removeAttribute(n) { delete attrs[n]; },
                addEventListener(n, h) { events[n] = h; }, removeEventListener(n) { delete events[n]; },
                appendChild(child) { if (child.parentNode) child.parentNode.removeChild(child); this.children.push(child); child.parentNode = this; },
                removeChild(child) { this.children.splice(this.children.indexOf(child), 1); child.parentNode = null; },
                insertBefore(child, before) { if (child.parentNode) child.parentNode.removeChild(child); this.children.splice(this.children.indexOf(before), 0, child); child.parentNode = this; },
                replaceChild(child, previous) { if (child.parentNode) child.parentNode.removeChild(child); this.children[this.children.indexOf(previous)] = child; previous.parentNode = null; child.parentNode = this; },
                remove() { if (this.parentNode) this.parentNode.removeChild(this); },
                getBoundingClientRect() { const rect = page.getBoundingClientRect(), scale = rect.width / page.offsetWidth; return {left: rect.left + (page.offsetWidth - 60) * scale, top: rect.top + 48 * scale, width: 44 * scale, height: 44 * scale}; }
            };
        }
        const sticky = {style: style(), getClientRects() { return [1]; }, getBoundingClientRect() {
            return {top: (this.style.getPropertyValue('position') === 'relative' ? -120 : 20) + parseFloat(this.style.getPropertyValue('top') || 0), left: 30};
        }};
        const content = {querySelectorAll() { return [sticky]; }};
        const page = {style: style(), classList: classes(), inert: false,
            get offsetWidth() { return parseFloat(this.style.getPropertyValue('width')) || 1264; },
            get offsetHeight() { return win.innerHeight; },
            getBoundingClientRect() {
                const revealed = this.classList.contains('account-page--revealed');
                const scale = revealed && win.innerWidth < 768 ? 0.94 : 1;
                return {width: this.offsetWidth * scale, top: this.offsetHeight * (1 - scale) / 2,
                    left: revealed ? this.offsetWidth * (1 - scale) - parseFloat(this.style.getPropertyValue('--account-reveal-offset')) : 0};
            }, querySelector() { return content; }};
        const body = Object.assign(node(), {style: style({'min-height': '100vh'}), scrollHeight: 1800, clientWidth: 1264});
        const backdrops = [];
        const panel = Object.assign(node(), {matches(selector) { return selector === '[data-account-reveal]'; },
            getBoundingClientRect() { return {width: 272}; }, parentElement: {querySelectorAll() { return backdrops; }}});
        const opener = Object.assign(node(), {id: 'navbarDropdown', focuses: 0, clicks: 0,
            getClientRects() { return [1]; }, focus(options) { if (options) assert(options.preventScroll); this.focuses++; doc.activeElement = this; }, click() { this.clicks++; }});
        opener.setAttribute('aria-label', 'Account menu for Alex');
        opener.setAttribute('title', 'Alex');
        opener.setAttribute('aria-haspopup', 'dialog');
        opener.setAttribute('data-bs-toggle', 'offcanvas');
        const originalParent = node(); originalParent.appendChild(opener);
        const doc = {body, activeElement: opener, documentElement: {scrollHeight: 1800},
            querySelector(selector) { return selector === '[data-account-page]' ? page : (selector === '[data-account-navigation-toggle]' && withControl ? opener : null); },
            createElement() { return node(); },
            addEventListener(name, handler, capture) { handlers[name] = handler; if (name === 'hidden.bs.offcanvas') assert.strictEqual(capture, true); }};
        const win = {document: doc, scrollX: 0, scrollY: 480, innerWidth: 1280, innerHeight: 720,
            matchMedia() { return {matches: reduced}; },
            getComputedStyle(element) {
                if (element === body) return {paddingLeft: '0px', paddingRight: '0px'};
                if (element === page) return {getPropertyValue() { return win.innerWidth < 768 ? '0.94' : '1'; }};
                return {position: 'sticky'};
            },
            scrollTo(x, y) { this.restored = [x, y]; },
            requestAnimationFrame(callback) { frames.set(++frameId, callback); return frameId; },
            cancelAnimationFrame(id) { frames.delete(id); }, addEventListener(name, handler) { handlers[name] = handler; }};
        win.setTimeout = callback => { timers.set(++frameId, callback); return frameId; };
        win.clearTimeout = id => timers.delete(id);
        initialize(win);
        return {win, doc, page, body, sticky, panel, opener, originalParent, handlers, frames, backdrops,
            event(overrides = {}) { return {target: panel, relatedTarget: opener, defaultPrevented: false, ...overrides}; },
            flushTimers() { [...timers.values()].forEach(callback => callback()); timers.clear(); },
            flushFrames() { [...frames.values()].forEach(callback => callback()); frames.clear(); }};
    }

    const f = fixture();
    f.handlers['show.bs.offcanvas'](f.event({defaultPrevented: true}));
    assert(!f.page.inert, 'An intercepted opening cannot freeze the page');
    f.handlers['show.bs.offcanvas'](f.event({target: {matches() { return false; }}}));
    assert(!f.page.inert, 'Contextual drawers keep their existing presentation');
    f.handlers['show.bs.offcanvas'](f.event());
    assert(f.page.inert && f.page.classList.contains('account-page--prepared'));
    assert.strictEqual(f.page.style.getPropertyValue('width'), '1264px', 'Opening retains page layout width');
    assert.strictEqual(f.page.style.getPropertyValue('--account-page-scroll-y'), '-480px');
    assert.strictEqual(f.page.style.getPropertyValue('--account-reveal-offset'), '256px', 'Scrollbar gutter does not leave a gap beside the drawer');
    assert.strictEqual(f.sticky.getBoundingClientRect().top, 20, 'An already-stuck section menu stays in place');
    const backdrop = {classList: {values: new Set(), add(n) { this.values.add(n); }, remove(n) { this.values.delete(n); }}};
    f.backdrops.push(backdrop);
    await Promise.resolve();
    assert(backdrop.classList.values.has('account-reveal-backdrop'));
    f.flushFrames();
    assert(f.page.classList.contains('account-page--revealed'));
    f.handlers['hide.bs.offcanvas'](f.event({defaultPrevented: true}));
    assert(f.page.classList.contains('account-page--revealed'), 'A cancelled close leaves the page revealed');
    f.win.innerWidth = 320; f.body.clientWidth = 320; f.handlers.resize();
    assert.strictEqual(f.page.style.getPropertyValue('--account-reveal-offset'), '272px');
    f.handlers['hide.bs.offcanvas'](f.event());
    assert(!f.page.classList.contains('account-page--revealed') && f.page.inert);
    f.handlers['hidden.bs.offcanvas'](f.event());
    assert(!f.page.inert && !f.page.classList.contains('account-page--prepared'));
    assert.strictEqual(f.body.style.getPropertyValue('min-height'), '100vh');
    assert.strictEqual(f.sticky.style.getPropertyValue('position'), '');
    assert.strictEqual(f.page.style.getPropertyValue('width'), '');
    assert.deepStrictEqual(f.win.restored, [0, 480]);
    assert.strictEqual(f.opener.focuses, 1);

    const cancelled = fixture();
    const opening = cancelled.event();
    cancelled.handlers['show.bs.offcanvas'](opening);
    opening.defaultPrevented = true;
    await Promise.resolve();
    assert(!cancelled.page.inert && cancelled.frames.size === 0, 'Late cancellation restores the page before paint');

    const fast = fixture();
    fast.handlers['show.bs.offcanvas'](fast.event());
    fast.handlers['hide.bs.offcanvas'](fast.event());
    fast.handlers['hidden.bs.offcanvas'](fast.event());
    await Promise.resolve();
    fast.flushFrames();
    assert(!fast.page.inert && !fast.page.classList.contains('account-page--revealed'), 'A quick close cannot reopen the page animation');

    const motion = fixture(true);
    motion.handlers['show.bs.offcanvas'](motion.event());
    await Promise.resolve();
    assert(motion.page.classList.contains('account-page--revealed') && motion.frames.size === 0, 'Reduced motion skips the animated frame');
    motion.doc.querySelector = selector => selector === '[data-account-page]' ? motion.page : {};
    motion.handlers['hidden.bs.offcanvas'](motion.event());
    assert.strictEqual(motion.opener.focuses, 0, 'A successor overlay owns focus during a handoff');

    const outside = fixture(false, true);
    const first = {getClientRects() { return [1]; }, focus() { outside.doc.activeElement = this; }};
    const last = {getClientRects() { return [1]; }, focus() { outside.doc.activeElement = this; }};
    outside.panel.querySelectorAll = () => [first, last];
    outside.handlers['show.bs.offcanvas'](outside.event());
    assert(!outside.opener.classList.contains('account-circle--close'), 'Prepare the original avatar before starting its crossfade with the page movement');
    assert.strictEqual(outside.opener.getAttribute('data-bs-toggle'), 'offcanvas', 'The opening click must finish before switching Bootstrap delegated actions');
    await Promise.resolve();
    assert.strictEqual(outside.opener.getAttribute('data-bs-toggle'), 'offcanvas', 'A microtask is still inside the delegated opening-click dispatch');
    outside.flushTimers(); outside.flushFrames();
    assert(outside.opener.classList.contains('account-circle--close'));
    assert.strictEqual(outside.opener.getAttribute('aria-label'), 'Close account menu');
    assert.strictEqual(outside.opener.getAttribute('data-bs-toggle'), null);
    assert.strictEqual(outside.opener.getAttribute('data-bs-dismiss'), 'offcanvas');
    assert.strictEqual(outside.panel.getAttribute('aria-owns'), 'navbarDropdown');
    assert.notStrictEqual(outside.opener.parentNode, outside.originalParent, 'The original control leaves the inactive page without cloning');
    const host = outside.opener.parentNode;
    assert.strictEqual(parseFloat(host.style.getPropertyValue('left')), 1204, 'The animation starts at the original horizontal position');
    assert.strictEqual(parseFloat(host.style.getPropertyValue('top')), 48, 'The animation starts at the original vertical position');
    assert.strictEqual(parseFloat(host.style.getPropertyValue('left')) + parseFloat(host.style.getPropertyValue('--account-toggle-offset')), 948, 'The close circle ends 16px inside the shifted page corner');
    assert.strictEqual(parseFloat(host.style.getPropertyValue('top')) + parseFloat(host.style.getPropertyValue('--account-toggle-offset-y')), 16, 'The close circle moves up to the corner');
    function key(value, shift = false) {
        const event = {key: value, shiftKey: shift, preventDefault() { this.prevented = true; }, stopPropagation() {}};
        outside.handlers.keydown(event);
        assert(event.prevented);
    }
    outside.doc.activeElement = outside.panel; key('Tab');
    assert.strictEqual(outside.doc.activeElement, outside.opener);
    key('Tab'); assert.strictEqual(outside.doc.activeElement, first);
    key('Tab', true); assert.strictEqual(outside.doc.activeElement, outside.opener);
    key('Tab', true); assert.strictEqual(outside.doc.activeElement, last);
    key('Tab'); assert.strictEqual(outside.doc.activeElement, outside.opener);
    outside.win.innerWidth = 320; outside.win.innerHeight = 568; outside.body.clientWidth = 320; outside.handlers.resize();
    assert.strictEqual(parseFloat(host.style.getPropertyValue('left')) + parseFloat(host.style.getPropertyValue('--account-toggle-offset')), 2, 'The outside close circle stays fully visible in a narrow page strip');
    assert(Math.abs(parseFloat(host.style.getPropertyValue('top')) + parseFloat(host.style.getPropertyValue('--account-toggle-offset-y')) - 33.04) < 0.001, 'The close control follows the mobile page inset while keeping its full hit area');
    assert.strictEqual(host.style.getPropertyValue('top'), '48px', 'Resizing a scaled page retains the original button position for the return animation');
    outside.win.innerWidth = 1280; outside.win.innerHeight = 720; outside.body.clientWidth = 1264; outside.handlers.resize();
    assert.strictEqual(host.style.getPropertyValue('left'), '1204px', 'Returning to desktop removes mobile scale without coordinate drift');
    assert.strictEqual(parseFloat(host.style.getPropertyValue('top')) + parseFloat(host.style.getPropertyValue('--account-toggle-offset-y')), 16);
    key('Escape'); assert.strictEqual(outside.opener.clicks, 1);
    outside.handlers['hide.bs.offcanvas'](outside.event());
    assert(!host.classList.contains('account-reveal-toggle--revealed'), 'Closing animates the same control back before restoring it to the header');
    assert(!outside.opener.classList.contains('account-circle--close'), 'The avatar and background start fading back during the return movement, before hidden');
    outside.handlers['hidden.bs.offcanvas'](outside.event());
    assert.strictEqual(outside.opener.parentNode, outside.originalParent);
    assert.strictEqual(outside.originalParent.children.length, 1);
    assert.strictEqual(outside.opener.getAttribute('aria-label'), 'Account menu for Alex');
    assert.strictEqual(outside.opener.getAttribute('data-bs-toggle'), 'offcanvas');
    assert.strictEqual(outside.opener.getAttribute('data-bs-dismiss'), null);
    assert(!outside.opener.classList.contains('account-circle--close'));
    assert.strictEqual(outside.panel.getAttribute('aria-owns'), null);
    console.log('Passed: account reveal scope, cancelled/rapid transitions, scrollbar/sticky geometry, resize, scroll/focus restoration and reduced motion.');
};
