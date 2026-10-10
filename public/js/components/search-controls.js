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
    var committed = readUrl(new URL(root.location.href));

    function defaults() { return {sort: 'relevance', facets: {}, audio_only: false, score_only: false}; }
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
                return Array.from(group.querySelectorAll('[data-search-value]')).some(function (button) { return button.getAttribute('data-search-value') === value; });
            });
        });
        state.audio_only = url.searchParams.get('audio_only') === '1';
        state.score_only = url.searchParams.get('score_only') === '1';
        return state;
    }
    function selected(group) {
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
            Array.from(group.querySelectorAll('[data-search-value]')).forEach(function (button) {
                button.setAttribute('aria-pressed', values.indexOf(button.getAttribute('data-search-value')) !== -1 ? 'true' : 'false');
            });
        });
        form.querySelector('[name=audio_only]').checked = state.audio_only;
        form.querySelector('[name=score_only]').checked = state.score_only;
        syncRange();
    }
    function collect() {
        var state = defaults();
        state.sort = form.querySelector('[name=sort]:checked').value;
        groups.forEach(function (group) { state.facets[group.getAttribute('data-search-facet')] = selected(group); });
        state.audio_only = form.querySelector('[name=audio_only]').checked;
        state.score_only = form.querySelector('[name=score_only]').checked;
        return state;
    }
    function entries(state) {
        var params = [['sort', state.sort]];
        Object.keys(state.facets).forEach(function (facet) {
            state.facets[facet].forEach(function (value) { params.push(['facets[' + facet + '][]', value]); });
        });
        if (state.audio_only) params.push(['audio_only', '1']);
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
        var active = committed.sort !== 'relevance' || committed.audio_only || committed.score_only || Object.keys(committed.facets).some(function (facet) { return committed.facets[facet].length > 0; });
        toggle.classList.toggle('is-active', active);
    }
    panel.addEventListener('show.bs.offcanvas', function () { render(committed); });
    panel.querySelector('[data-search-reset]').addEventListener('click', function () { render(defaults()); });
    groups.forEach(function (group) {
        group.addEventListener('click', function (event) {
            var button = event.target.closest('[data-search-value]');
            if (!button || !group.contains(button)) return;
            button.setAttribute('aria-pressed', button.getAttribute('aria-pressed') === 'true' ? 'false' : 'true');
            if (group.getAttribute('data-search-facet') === 'length') syncRange();
        });
    });
    [minimum, maximum].forEach(function (input) {
        input.addEventListener('input', function () {
            if (Number(minimum.value) > Number(maximum.value)) {
                if (input === minimum) maximum.value = minimum.value;
                else minimum.value = maximum.value;
            }
            var group = groups.find(function (group) { return group.getAttribute('data-search-facet') === 'length'; });
            Array.from(group.querySelectorAll('[data-search-value]')).forEach(function (button) {
                var index = lengths.indexOf(button.getAttribute('data-search-value'));
                button.setAttribute('aria-pressed', index >= Number(minimum.value) && index <= Number(maximum.value) ? 'true' : 'false');
            });
            syncRange();
        });
    });
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        committed = collect();
        updateSearch();
        root.bootstrap.Offcanvas.getOrCreateInstance(panel).hide();
        if (typeof root.applyFilters === 'function' && doc.getElementById('pieces-list')) {
            var url = new URL(root.location.href);
            Array.from(url.searchParams.keys()).forEach(function (key) {
                if (key === 'sort' || key === 'audio_only' || key === 'score_only' || key === 'page' || key.indexOf('filters[') === 0 || key.indexOf('facets[') === 0) url.searchParams.delete(key);
            });
            entries(committed).forEach(function (pair) { url.searchParams.append(pair[0], pair[1]); });
            root.history.replaceState(root.history.state, '', url.toString());
            root.reset();
            root.applyFilters([]);
        } else {
            if (!search.querySelector('[name=search]').value.trim()) {
                var catalogue = doc.createElement('input'); catalogue.type = 'hidden'; catalogue.name = 'catalogue'; catalogue.value = '1'; search.appendChild(catalogue);
            }
            search.requestSubmit();
        }
    });
    render(committed);
    updateSearch();
}));
