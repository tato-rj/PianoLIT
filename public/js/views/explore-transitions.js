(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory;
    else factory(root);
}(typeof window !== 'undefined' ? window : this, function (root) {
    'use strict';
    var document = root.document, route = new URL(root.location.href);
    var pendingKey = 'pianolit.explore.transition', directoryKey = 'pianolit.explore.directory';
    var lastLink = null;
    var facets = ['level', 'mood', 'tag', 'composers', 'country'];
    function enabled() {
        return root.matchMedia('(max-width: 991.98px)').matches &&
            !root.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }
    function selection(url) {
        var result = {};
        facets.forEach(function (key) { if (url.searchParams.get(key)) result[key] = url.searchParams.get(key); });
        return result;
    }
    function direction(from, to) {
        try {
            from = new URL(from); to = new URL(to);
            if (from.origin !== route.origin || to.origin !== route.origin || from.pathname !== route.pathname || to.pathname !== route.pathname) return null;
            var before = selection(from), after = selection(to);
            var oldKeys = Object.keys(before), newKeys = Object.keys(after);
            if (newKeys.length > oldKeys.length && oldKeys.every(function (key) { return before[key] === after[key]; })) return 'forward';
            if (newKeys.length < oldKeys.length && newKeys.every(function (key) { return before[key] === after[key]; })) return 'back';
        } catch (error) { /* Unknown destinations keep ordinary navigation. */ }
        return null;
    }
    function read(key) {
        try { return JSON.parse(root.sessionStorage.getItem(key)); } catch (error) { return null; }
    }
    function write(key, value) {
        try { root.sessionStorage.setItem(key, JSON.stringify(value)); } catch (error) { /* Storage is optional. */ }
    }
    function clearDirection() { document.documentElement.removeAttribute('data-explore-transition'); }
    function apply(event, motion) {
        clearDirection();
        if (!event.viewTransition) return;
        if (!enabled() || !motion) { event.viewTransition.skipTransition(); return; }
        document.documentElement.setAttribute('data-explore-transition', motion);
        event.viewTransition.finished.then(clearDirection, clearDirection);
    }
    function saveDirectory() {
        var sections = Array.prototype.slice.call(document.querySelectorAll('.explore-directory > details'));
        write(directoryKey, {path: route.pathname, open: sections.map(function (section) { return section.open; }), scroll: root.scrollY});
    }
    function restoreDirectory() {
        var saved = read(directoryKey);
        if (!saved || saved.path !== route.pathname || !Array.isArray(saved.open)) return;
        document.querySelectorAll('.explore-directory > details').forEach(function (section, index) { section.open = saved.open[index] === true; });
        if (Number.isFinite(saved.scroll) && saved.scroll >= 0) root.scrollTo(0, saved.scroll);
    }
    document.documentElement.setAttribute('data-explore-page', '');
    // Observe real links; never intercept navigation, history, or modified clicks.
    document.addEventListener('click', function (event) {
        lastLink = null;
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        var link = event.target.closest('a[href]');
        if (link && !link.hasAttribute('download') && (!link.target || link.target === '_self')) lastLink = link.href;
    });
    root.addEventListener('pageswap', function (event) {
        var to = event.activation && event.activation.entry ? event.activation.entry.url : lastLink;
        var motion = direction(root.location.href, to);
        if (motion && enabled()) {
            write(pendingKey, {from: root.location.href, to: to, time: Date.now()});
            if (Object.keys(selection(new URL(root.location.href))).length === 0) saveDirectory();
        }
        apply(event, motion);
        lastLink = null;
    });
    root.addEventListener('pagereveal', function (event) {
        var activation = root.navigation && root.navigation.activation;
        var pending = read(pendingKey);
        var from = activation && activation.from ? activation.from.url :
            pending && pending.to === root.location.href && Date.now() - pending.time < 30000 ? pending.from : null;
        var motion = direction(from, root.location.href);
        if (enabled() && motion === 'back' && Object.keys(selection(new URL(root.location.href))).length === 0) restoreDirectory();
        apply(event, motion);
        try { root.sessionStorage.removeItem(pendingKey); } catch (error) { /* Storage is optional. */ }
    });
    return {direction: direction};
}));
