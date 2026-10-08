(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory;
    else factory(root);
}(typeof window !== 'undefined' ? window : this, function (root) {
    'use strict';
    var document = root.document, list = document.getElementById('pieces-list');
    if (!list || !list.getAttribute('data-highlights-url')) return;
    var loading = document.getElementById('highlights-loading');
    var empty = document.getElementById('highlights-empty');
    var error = document.getElementById('highlights-error');
    var filter = document.getElementById('server-filter');
    var generation = 0, pending = null, interrupted = false;

    function load() {
        var request = ++generation;
        if (pending) pending.cancel();
        pending = root.axios.CancelToken.source();
        var filters = [];
        filter.querySelectorAll('.options-columns > div').forEach(function (group) {
            var names = Array.prototype.map.call(group.querySelectorAll('input[type="checkbox"]:checked'), function (input) { return input.value; });
            if (names.length) filters.push(JSON.stringify(names));
        });
        list.innerHTML = '';
        list.setAttribute('aria-busy', 'true');
        loading.hidden = false;
        empty.hidden = error.hidden = true;
        root.axios.get(list.getAttribute('data-highlights-url'), {
            params: {filters: filters}, cancelToken: pending.token, timeout: 20000
        }).then(function (response) {
            if (request !== generation) return;
            list.innerHTML = response.data;
            empty.hidden = list.children.length > 0;
            var sort = document.querySelector('#sort-container input:checked');
            if (sort) root.jQuery(list).sortChildrenBy(sort);
        }).catch(function (reason) {
            if (request !== generation || root.axios.isCancel(reason)) return;
            error.hidden = false;
        }).then(function () {
            if (request !== generation) return;
            pending = null;
            loading.hidden = true;
            list.setAttribute('aria-busy', 'false');
        });
    }

    filter.addEventListener('change', function (event) {
        if (event.target.type === 'checkbox') load();
    });
    document.getElementById('highlights-retry').addEventListener('click', load);
    root.addEventListener('pagehide', function () {
        if (!pending) return;
        interrupted = true;
        generation++;
        pending.cancel();
        pending = null;
    });
    root.addEventListener('pageshow', function () {
        if (!interrupted) return;
        interrupted = false;
        load();
    });
}));
