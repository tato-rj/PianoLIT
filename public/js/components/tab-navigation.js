// Also embedded by the Blade component: initial selection must precede the main bundle.
(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory();
    else root.PianoLITTabNavigation = factory();
})(typeof window !== 'undefined' ? window : this, function () {
function panelId(trigger) {
    const target = trigger.getAttribute('data-bs-target') || trigger.getAttribute('href') || '';
    if (!target.startsWith('#')) return null;
    try { return decodeURIComponent(target.slice(1)); }
    catch (error) { return null; }
}

function available(trigger, doc) {
    const id = trigger && panelId(trigger);
    return id && !trigger.disabled && !trigger.classList.contains('disabled') &&
        trigger.getAttribute('aria-disabled') !== 'true' && doc.getElementById(id);
}

function initializeTabNavigation(win) {
    const doc = win.document;
    let restoring = false;
    let defaults = [];

    function restore() {
        if (!win.bootstrap || !win.bootstrap.Tab) return;
        let targets = defaults;
        if (win.location.hash) {
            let id;
            try { id = decodeURIComponent(win.location.hash.slice(1)); }
            catch (error) { return; }
            // Compare IDs rather than interpolating an untrusted hash into a selector.
            const trigger = Array.from(doc.querySelectorAll('[data-tab-navigation] [data-bs-toggle="tab"]'))
                .find(item => panelId(item) === id);
            targets = trigger ? [trigger] : [];
        }
        restoring = true;
        try {
            targets.filter(trigger => available(trigger, doc)).forEach(trigger => win.bootstrap.Tab.getOrCreateInstance(trigger).show());
        } finally { restoring = false; }
    }

    // Listen for the actual selection, including keyboard and programmatic changes.
    doc.addEventListener('shown.bs.tab', function (event) {
        const trigger = event.target;
        if (restoring || !trigger.matches('[data-bs-toggle="tab"]') ||
            !trigger.closest('[data-tab-navigation]') || !available(trigger, doc)) return;
        const hash = '#' + encodeURIComponent(panelId(trigger));
        if (win.location.hash === hash) return;
        try {
            // Keep the path, query and existing Back-button metadata without adding entries.
            win.history.replaceState(win.history.state, '', hash);
        } catch (error) {
            // Tab selection still works if the browser disallows updating history.
        }
    });

    function start() {
        defaults = Array.from(doc.querySelectorAll('[data-tab-navigation]')).map(nav =>
            Array.from(nav.querySelectorAll('[data-bs-toggle="tab"]')).find(trigger =>
                panelId(trigger) === nav.getAttribute('data-tab-default')) ||
            nav.querySelector('[data-bs-toggle="tab"].active') || nav.querySelector('[data-bs-toggle="tab"]'));
        restore();
    }
    win.addEventListener('hashchange', restore);
    // Page-specific listeners (such as lazy invoices) are ready before restoration.
    if (doc.readyState === 'loading') doc.addEventListener('DOMContentLoaded', start, {once: true});
    else start();

}

initializeTabNavigation.prepare = function (frame, win) {
    if (!win.location.hash) return;
    // A streamed response may paint before all panes arrive. Reveal the whole group together.
    frame.style.visibility = 'hidden';
    frame.tabNavigationReveal = function () {
        frame.style.removeProperty('visibility');
        win.document.removeEventListener('DOMContentLoaded', frame.tabNavigationReveal);
        delete frame.tabNavigationReveal;
    };
    // Keep the server-rendered default usable even if the response ends before the final script.
    win.document.addEventListener('DOMContentLoaded', frame.tabNavigationReveal, {once: true});
};

initializeTabNavigation.initial = function (frame, win) {
    try {
        let id;
        try { id = decodeURIComponent(win.location.hash.slice(1)); }
        catch (error) { return; }
        const nav = frame.querySelector('[data-tab-navigation]');
        const tabs = Array.from(nav.querySelectorAll('[data-bs-toggle="tab"]'));
        const selected = tabs.find(trigger => panelId(trigger) === id);
        const panel = available(selected, win.document);
        if (!panel || !frame.contains(panel) || !panel.classList.contains('tab-pane')) return;
        // Set classes and accessibility atomically, without Bootstrap's tab-switch animation/events.
        const transition = panel.style.transition;
        panel.style.transition = 'none';
        tabs.forEach(trigger => {
            trigger.classList.toggle('active', trigger === selected);
            trigger.setAttribute('aria-selected', trigger === selected ? 'true' : 'false');
            trigger.setAttribute('tabindex', trigger === selected ? '0' : '-1');
        });
        Array.from(panel.parentElement.children).filter(item => item.classList.contains('tab-pane')).forEach(item => {
            item.classList.toggle('active', item === panel);
            item.classList.toggle('show', item === panel);
        });
        // Commit the final opacity before re-enabling transitions for subsequent user selections.
        void panel.offsetHeight;
        panel.style.transition = transition;
    } finally {
        if (frame.tabNavigationReveal) frame.tabNavigationReveal();
    }
};
return initializeTabNavigation;
});
