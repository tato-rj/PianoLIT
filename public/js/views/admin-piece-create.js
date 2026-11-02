(function (doc, win) {
    'use strict';
    var form = doc.querySelector('[data-piece-create]');
    if (!form) return;

    var watchedFields = ['name', 'collection_name', 'catalogue_number', 'collection_number', 'movement_number'];
    var panel = form.querySelector('#validation-results');
    var status = panel.querySelector('[data-duplicate-status]');
    var results = panel.querySelector('[data-duplicate-results]');
    var error = panel.querySelector('[data-duplicate-error]');
    var timer, pending, generation = 0;

    function checkExisting() {
        var request = generation;
        var values = {};
        watchedFields.forEach(function (name) { values[name] = form.elements.namedItem(name).value; });
        if (!values.name.trim()) {
            status.textContent = 'Enter a piece name to check for similar pieces.';
            return;
        }
        status.textContent = 'Checking existing pieces…';
        panel.setAttribute('aria-busy', 'true');
        pending = win.axios.CancelToken.source();
        win.axios.post(form.dataset.validateNameUrl, values, {
            cancelToken: pending.token,
            headers: {'X-CSRF-TOKEN': form.elements.namedItem('_token').value}
        }).then(function (response) {
            if (request !== generation) return;
            // The admin endpoint returns an escaped, server-rendered Blade list.
            results.innerHTML = response.data;
            var hasResults = !!results.querySelector('li');
            status.hidden = hasResults;
            status.textContent = hasResults ? '' : 'No similar pieces found.';
        }).catch(function (failure) {
            if (request !== generation || win.axios.isCancel(failure)) return;
            status.hidden = true;
            error.hidden = false;
        }).then(function () {
            if (request !== generation) return;
            pending = null;
            panel.setAttribute('aria-busy', 'false');
        });
    }

    function scheduleCheck() {
        generation++;
        win.clearTimeout(timer);
        if (pending) pending.cancel();
        pending = null;
        results.innerHTML = '';
        status.hidden = false;
        status.textContent = 'Enter a piece name to check for similar pieces.';
        error.hidden = true;
        panel.setAttribute('aria-busy', 'false');
        timer = win.setTimeout(checkExisting, 500);
    }
    form.addEventListener('input', function (event) {
        if (watchedFields.indexOf(event.target.name) !== -1) scheduleCheck();
    });

    if (win.Lukup) {
        ['score_publisher', 'score_editor', 'nickname', 'name'].forEach(function (name) {
            if (!form.elements.namedItem(name)) return;
            new win.Lukup({url: form.dataset.singleLookupUrl, field: name, autofill: [name]}).enable();
        });
        new win.Lukup({
            url: form.dataset.multiLookupUrl, field: 'collection_name',
            autofill: ['collection_name', 'nickname', 'score_url', 'catalogue_name', 'catalogue_number', 'composer_id', 'composed_in', 'published_in', 'score_editor', 'score_copyright', 'score_publisher', 'curiosity'],
            exclude: ['No information available']
        }).enable();
        // Lookup fills values directly; its click must also refresh duplicate results.
        form.addEventListener('click', function (event) {
            if (event.target.closest('.result-temp')) scheduleCheck();
        });
    }

    form.addEventListener('change', function (event) {
        if (event.target.name === 'tags[]') {
            form.querySelector('[data-tag-warning]').hidden = form.querySelectorAll('[name="tags[]"]:checked').length <= 7;
        }
    });
    win.addEventListener('pagehide', function () {
        generation++;
        win.clearTimeout(timer);
        if (pending) pending.cancel();
    });
    if (form.elements.namedItem('name').value.trim()) scheduleCheck();
}(document, window));
