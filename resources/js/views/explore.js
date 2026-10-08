(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory;
    else factory(root);
}(typeof window !== 'undefined' ? window : this, function (root) {
    'use strict';
    var doc = root.document;
    var page = doc.getElementById('explore-page');
    if (!page) return;
    var form = page.querySelector('#search-form');
    var input = form.querySelector('[name="search"]');
    var erase = form.querySelector('[data-erase]');
    var container = page.querySelector('#most-recent');
    var signedIn = !!(root.app && root.app.user);
    var recent = [];
    // Cookies may be missing, malformed, or contain arbitrary text; never render as HTML.
    if (signedIn) {
        try {
            var saved = JSON.parse(root.getCookie('pl_recent') || '[]');
            if (Array.isArray(saved)) recent = saved.filter(function (query) {
                return typeof query === 'string' && query.trim().length > 0 && query.length <= 18;
            }).slice(0, 5);
        } catch (error) { recent = []; }
    }
    recent.forEach(function (query) {
        var button = doc.createElement('button');
        button.type = 'button';
        button.className = 'recent-query btn-raw rounded-pill border m-1 d-inline-flex align-items-center gap-2';
        var icon = doc.createElement('i');
        icon.className = 'app-icon icon-search icon-size-sm text-muted';
        icon.setAttribute('aria-hidden', 'true');
        button.appendChild(icon);
        button.appendChild(doc.createTextNode(query));
        button.addEventListener('click', function () { input.value = query; form.requestSubmit(); });
        container.querySelector('div').appendChild(button);
    });
    if (recent.length) container.style.display = 'flex';
    function updateClear() { erase.style.display = input.value ? '' : 'none'; }
    input.addEventListener('input', updateClear);
    erase.addEventListener('click', function () { input.value = ''; updateClear(); input.focus(); });
    updateClear();
    form.addEventListener('submit', function () {
        if (!signedIn) return;
        var query = input.value.trim();
        if (!query || query.length > 18) return;
        recent = [query].concat(recent.filter(function (item) { return item !== query; })).slice(0, 5);
        root.setCookie('pl_recent', JSON.stringify(recent), 30);
    });
}));
