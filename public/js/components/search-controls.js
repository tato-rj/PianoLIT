(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory;
    else factory(root);
}(typeof window !== 'undefined' ? window : this, function (root) {
    'use strict';
    var doc = root.document, panel = doc.getElementById('search-controls');
    if (!panel) return;
    var form = doc.getElementById('search-controls-form');
    var search = doc.getElementById('search-form');
    var toggle = search.querySelector('.search-controls-toggle');
    var groups = Array.from(panel.querySelectorAll('[data-search-facet]'));
    var lengths = ['short', 'medium', 'long'];
    var minimum = panel.querySelector('[data-length-min]');
    var maximum = panel.querySelector('[data-length-max]');
    var range = panel.querySelector('.search-length');
    var draftLength = [];
    var isResults = !!doc.getElementById('pieces-list');
    var pendingKey = 'pianolit.search.pending-controls';
    var initialUrl = new URL(root.location.href);
    try {
        var pending = JSON.parse(root.sessionStorage.getItem(pendingKey));
        if (isResults) root.sessionStorage.removeItem(pendingKey);
        if (Array.isArray(pending) && pending.length <= 40) {
            clearOptions(initialUrl);
            pending.forEach(function (pair) {
                if (Array.isArray(pair) && pair.length === 2 && typeof pair[0] === 'string' && typeof pair[1] === 'string') initialUrl.searchParams.append(pair[0], pair[1]);
            });
        }
    } catch (error) { /* Pending preferences still work in the current page without storage. */ }
    var committed = readUrl(initialUrl);

    function clearOptions(url) {
        Array.from(url.searchParams.keys()).forEach(function (key) {
            if (key === 'sort' || key === 'video_only' || key === 'audio_only' || key === 'score_only' || key === 'filters' || key === 'facets' || key.indexOf('filters[') === 0 || key.indexOf('facets[') === 0) url.searchParams.delete(key);
        });
        return url;
    }
    // Keep applied choices in memory. The visible results URL contains only
    // the search/catalogue context, so refreshing starts with default controls.
    root.searchControlsUrl = function (base) {
        var url = clearOptions(new URL(base, root.location.href));
        entries(committed).forEach(function (pair) { url.searchParams.append(pair[0], pair[1]); });
        return url.toString();
    };
    if (isResults) {
        root.history.replaceState(root.history.state, '', clearOptions(new URL(root.location.href)).toString());
    }

    function defaults() { return {sort: 'relevance', facets: {}, video_only: false, score_only: false}; }
    function readUrl(url) {
        var state = defaults();
        var sort = url.searchParams.get('sort');
        if (Array.from(form.querySelectorAll('[name=sort]')).some(function (input) { return input.value === sort; })) state.sort = sort;
        groups.forEach(function (group) {
            var facet = group.getAttribute('data-search-facet');
            var values = url.searchParams.getAll('facets[' + facet + '][]');
            url.searchParams.forEach(function (value, key) {
                if (key.indexOf('filters[') !== 0) return;
                try {
                    var names = JSON.parse(value);
                    if (Array.isArray(names)) values = values.concat(names);
                } catch (error) {}
            });
            // Axios serializes nested arrays with indices; accept both encodings.
            url.searchParams.forEach(function (value, key) {
                if (key.indexOf('facets[' + facet + '][') === 0 && key !== 'facets[' + facet + '][]') values.push(value);
            });
            state.facets[facet] = values.filter(function (value) {
                if (facet === 'length') return lengths.indexOf(value) !== -1;
                return Array.from(group.querySelectorAll('[data-search-value]')).some(function (button) { return button.getAttribute('data-search-value') === value; });
            });
        });
        state.video_only = url.searchParams.get('video_only') === '1';
        state.score_only = url.searchParams.get('score_only') === '1';
        return state;
    }
    function selected(group) {
        if (group.getAttribute('data-search-facet') === 'length') return draftLength.slice();
        return Array.from(group.querySelectorAll('[aria-pressed=true]')).map(function (button) { return button.getAttribute('data-search-value'); });
    }
    function lengthSelection() { return selected(groups.find(function (group) { return group.getAttribute('data-search-facet') === 'length'; })); }
    function syncRange() {
        var values = lengthSelection();
        minimum.value = values.length ? Math.min.apply(null, values.map(function (value) { return lengths.indexOf(value); })) : 0;
        maximum.value = values.length ? Math.max.apply(null, values.map(function (value) { return lengths.indexOf(value); })) : 2;
        range.style.setProperty('--range-start', Number(minimum.value) * 50 + '%');
        range.style.setProperty('--range-end', Number(maximum.value) * 50 + '%');
        minimum.style.zIndex = Number(minimum.value) === 2 ? '2' : '0';
        minimum.setAttribute('aria-valuetext', lengths[Number(minimum.value)]);
        maximum.setAttribute('aria-valuetext', lengths[Number(maximum.value)]);
        panel.querySelector('[data-length-summary]').textContent = values.length ? values.map(function (value) { return value.charAt(0).toUpperCase() + value.slice(1); }).join(', ') : 'Any length';
    }
    function render(state) {
        Array.from(form.querySelectorAll('[name=sort]')).forEach(function (input) { input.checked = input.value === state.sort; });
        groups.forEach(function (group) {
            var values = state.facets[group.getAttribute('data-search-facet')] || [];
            if (group.getAttribute('data-search-facet') === 'length') draftLength = values.slice();
            Array.from(group.querySelectorAll('[data-search-value]')).forEach(function (button) {
                button.setAttribute('aria-pressed', values.indexOf(button.getAttribute('data-search-value')) !== -1 ? 'true' : 'false');
            });
        });
        form.querySelector('[name=video_only]').checked = state.video_only;
        form.querySelector('[name=score_only]').checked = state.score_only;
        syncRange();
    }
    function collect() {
        var state = defaults();
        state.sort = form.querySelector('[name=sort]:checked').value;
        groups.forEach(function (group) { state.facets[group.getAttribute('data-search-facet')] = selected(group); });
        state.video_only = form.querySelector('[name=video_only]').checked;
        state.score_only = form.querySelector('[name=score_only]').checked;
        return state;
    }
    function entries(state) {
        var params = [['sort', state.sort]];
        Object.keys(state.facets).forEach(function (facet) {
            state.facets[facet].forEach(function (value) { params.push(['facets[' + facet + '][]', value]); });
        });
        if (state.video_only) params.push(['video_only', '1']);
        if (state.score_only) params.push(['score_only', '1']);
        return params;
    }
    function updateSearch() {
        Array.from(search.querySelectorAll('[data-search-committed]')).forEach(function (input) { input.remove(); });
        entries(committed).forEach(function (pair) {
            var input = doc.createElement('input');
            input.type = 'hidden'; input.name = pair[0]; input.value = pair[1];
            input.setAttribute('data-search-committed', ''); search.appendChild(input);
        });
        var active = committed.sort !== 'relevance' || committed.video_only || committed.score_only || Object.keys(committed.facets).some(function (facet) { return committed.facets[facet].length > 0; });
        toggle.classList.toggle('is-active', active);
    }
    panel.addEventListener('show.bs.offcanvas', function () { render(committed); });
    panel.querySelector('[data-search-reset]').addEventListener('click', function () { render(defaults()); });
    groups.forEach(function (group) {
        group.addEventListener('click', function (event) {
            var button = event.target.closest('[data-search-value]');
            if (!button || !group.contains(button)) return;
            button.setAttribute('aria-pressed', button.getAttribute('aria-pressed') === 'true' ? 'false' : 'true');
        });
    });
    [minimum, maximum].forEach(function (input) {
        input.addEventListener('input', function () {
            if (Number(minimum.value) > Number(maximum.value)) {
                if (input === minimum) maximum.value = minimum.value;
                else minimum.value = maximum.value;
            }
            draftLength = lengths.slice(Number(minimum.value), Number(maximum.value) + 1);
            if (draftLength.length === lengths.length) draftLength = [];
            syncRange();
        });
    });
    form.addEventListener('submit', function (event) { event.preventDefault(); });
    panel.querySelector('[data-search-apply]').addEventListener('click', function () {
        committed = collect();
        updateSearch();
        root.bootstrap.Offcanvas.getOrCreateInstance(panel).hide();
        if (isResults) {
            root.reset();
            root.applyFilters([]);
        } else {
            // Hand off only when a later search/navigation reaches results.
            // Explore selections can visit several guide pages in between.
            try { root.sessionStorage.setItem(pendingKey, JSON.stringify(entries(committed))); } catch (error) {}
        }
    });
    search.addEventListener('submit', function (event) {
        if (!isResults) return;
        event.preventDefault();
        var url = clearOptions(new URL(search.action, root.location.href));
        url.searchParams.set('search', search.querySelector('[name=search]').value);
        root.history.replaceState(root.history.state, '', url.toString());
        root.reset();
        root.applyFilters([]);
    });
    render(committed);
    updateSearch();
}));
