(function () {
    'use strict';
    var root = document.getElementById('piece-timeline-admin');
    if (!root) return;
    document.body.classList.add('piece-timeline-editor');
    var form = document.getElementById('timeline-search');
    var year = document.getElementById('reference-year');
    var find = document.getElementById('timeline-find');
    var more = document.getElementById('timeline-more');
    var status = document.getElementById('timeline-search-status');
    var candidates = document.getElementById('timeline-candidates');
    var saved = document.getElementById('timeline-saved');
    var resultsHeading = document.getElementById('timeline-results-heading');
    var candidateCounter = document.getElementById('timeline-candidate-count');
    var savedCounter = document.getElementById('timeline-saved-count');
    var pageTitle = root.querySelector('#page-title h5');
    var candidateCount = 0;
    var savedCount = Number(savedCounter.textContent);
    var searchId = null;
    var busy = false;
    var generation = 0;
    var storageKey = 'pianolit.timeline.' + (root.dataset.searchKey || root.dataset.pieceId);
    var title = root.dataset.title || 'Timeline';
    var typeInputs = Array.prototype.slice.call(form.querySelectorAll('.timeline-type'));
    try {
        var previous = JSON.parse(sessionStorage.getItem(storageKey));
        if (previous && previous.year) { year.value = previous.year; searchId = previous.searchId || null; }
        if (previous && Array.isArray(previous.types) && previous.types.length && previous.types.every(function (type) {
            return typeInputs.some(function (input) { return input.value === type; });
        })) typeInputs.forEach(function (input) { input.checked = previous.types.indexOf(input.value) !== -1; });
        else if (previous && previous.types) searchId = null;
    } catch (e) {}
    more.hidden = true;
    resultsHeading.hidden = true;
    updateSavedCount(savedCount);
    arrangeSaved();

    function selectedTypes() {
        return typeInputs.filter(function (input) { return input.checked; }).map(function (input) { return input.value; });
    }
    function rememberSearch() {
        var state = {year: year.value, searchId: searchId};
        if (typeInputs.length) state.types = selectedTypes();
        try { sessionStorage.setItem(storageKey, JSON.stringify(state)); } catch (e) {}
    }

    function arrangeSaved() {
        var rows = Array.prototype.slice.call(saved.querySelectorAll('[data-event-id]'));
        rows.sort(function (a, b) { return a.dataset.sort.localeCompare(b.dataset.sort); });
        if (saved.dataset.groupDecades !== 'true') {
            rows.forEach(function (row) { saved.appendChild(row); });
            return;
        }
        var decades = {};
        rows.forEach(function (row) {
            var decade = Math.floor(Number(row.dataset.year) / 10) * 10;
            var group = saved.querySelector('[data-decade="' + decade + '"]');
            if (!group) {
                group = document.createElement('details');
                group.className = 'card timeline-saved-decade';
                group.dataset.decade = String(decade);
                var summary = document.createElement('summary');
                summary.className = 'timeline-decade-summary';
                summary.textContent = decade + '–' + Math.min(9999, decade + 9) + ' ';
                var count = document.createElement('span');
                count.className = 'timeline-decade-count badge bg-light text-muted';
                summary.appendChild(count);
                var list = document.createElement('div');
                list.className = 'timeline-decade-events';
                group.appendChild(summary);
                group.appendChild(list);
                saved.appendChild(group);
            }
            group.querySelector('.timeline-decade-events').appendChild(row);
            decades[decade] = group;
        });
        // Move existing nodes to preserve open decades and in-progress edits.
        Array.prototype.forEach.call(saved.querySelectorAll('[data-decade]'), function (group) {
            if (!decades[group.dataset.decade]) group.remove();
        });
        Object.keys(decades).sort(function (a, b) { return Number(a) - Number(b); }).forEach(function (decade) {
            var group = decades[decade];
            var count = group.querySelectorAll('[data-event-id]').length;
            group.querySelector('.timeline-decade-count').textContent = count + (count === 1 ? ' event' : ' events');
            saved.appendChild(group);
        });
    }

    function updateSavedCount(count) {
        savedCount = count;
        savedCounter.textContent = String(count);
        if (pageTitle && root.dataset.titleCount !== 'false') pageTitle.textContent = title + ' · ' + count + (count === 1 ? ' event' : ' events');
        document.getElementById('timeline-empty').hidden = count > 0;
    }

    function arrangeCandidates(referenceYear) {
        var cards = Array.prototype.slice.call(candidates.querySelectorAll('[data-candidate]'));
        cards.sort(function (a, b) {
            return a.dataset.date.localeCompare(b.dataset.date) || a.dataset.candidate.localeCompare(b.dataset.candidate);
        });
        if (root.dataset.groupCandidates === 'false') {
            cards.forEach(function (card) { candidates.appendChild(card); });
            return;
        }
        [
            {key: 'before', label: 'Before ' + referenceYear, match: function (value) { return value < referenceYear; }},
            {key: 'same', label: 'In ' + referenceYear, match: function (value) { return value === referenceYear; }},
            {key: 'after', label: 'After ' + referenceYear, match: function (value) { return value > referenceYear; }}
        ].forEach(function (period) {
            var matches = cards.filter(function (card) { return period.match(Number(card.dataset.year)); });
            if (!matches.length) return;
            var group = candidates.querySelector('[data-period="' + period.key + '"]');
            if (!group) {
                group = document.createElement('section');
                group.className = 'timeline-candidate-group';
                group.dataset.period = period.key;
                var heading = document.createElement('h6');
                heading.className = 'timeline-candidate-group-heading';
                heading.id = 'timeline-period-' + period.key;
                group.setAttribute('aria-labelledby', heading.id);
                var list = document.createElement('div');
                list.className = 'timeline-candidates-list';
                group.appendChild(heading);
                group.appendChild(list);
            }
            group.querySelector('.timeline-candidate-group-heading').textContent = period.label + ' · ' + matches.length + (matches.length === 1 ? ' event' : ' events');
            var list = group.querySelector('.timeline-candidates-list');
            matches.forEach(function (card) { list.appendChild(card); });
            // Move existing nodes so saved/expanded states survive later batches.
            candidates.appendChild(group);
        });
    }

    function request(url, data) {
        return window.axios.post(url, data, {headers: {'X-CSRF-TOKEN': root.dataset.csrf, 'Accept': 'application/json'}});
    }
    function message(error, fallback) {
        var data = error.response && error.response.data;
        if (error.response && error.response.status === 422 && data.errors) {
            return Object.keys(data.errors).map(function (key) { return data.errors[key][0]; }).join(' ');
        }
        return data && data.message && error.response.status === 503 ? data.message : fallback;
    }
    function setBusy(value) {
        busy = value;
        find.disabled = value;
        more.disabled = value;
        year.disabled = value;
        typeInputs.forEach(function (input) { input.disabled = value; });
        Array.prototype.forEach.call(candidates.querySelectorAll('.timeline-save'), function (button) {
            button.disabled = value || button.dataset.saved === 'true';
        });
        Array.prototype.forEach.call(saved.querySelectorAll('button[type="submit"]'), function (button) { button.disabled = value; });
        form.setAttribute('aria-busy', value ? 'true' : 'false');
    }
    function discover(reset) {
        if (busy || !form.reportValidity()) return;
        if (typeInputs.length && !selectedTypes().length) {
            status.textContent = 'Choose at least one event type.';
            typeInputs[0].focus();
            return;
        }
        if (reset) {
            searchId = null;
            candidates.textContent = '';
            candidateCount = 0;
            candidateCounter.textContent = '0';
            resultsHeading.hidden = true;
            more.hidden = true;
            generation++;
        }
        var current = generation;
        setBusy(true);
        status.textContent = 'Finding historical events…';
        var data = {reference_year: Number(year.value), search_id: searchId};
        if (typeInputs.length) data.types = selectedTypes();
        request(root.dataset.discoverUrl, data)
            .then(function (response) {
                if (current !== generation) return;
                var data = response.data;
                searchId = data.search_id;
                candidates.insertAdjacentHTML('beforeend', data.html);
                arrangeCandidates(Number(year.value));
                candidateCount += data.count;
                candidateCounter.textContent = String(candidateCount);
                resultsHeading.hidden = !candidateCount;
                more.hidden = !candidateCount || !data.has_more;
                if (!candidateCount && !data.has_more) searchId = null;
                rememberSearch();
                var period = data.start_year !== undefined ? data.start_year + '–' + data.end_year : 'within ' + data.range + ' years of ' + year.value;
                var exhaustedPeriod = data.start_year !== undefined ? 'from ' + data.start_year + ' to ' + data.end_year : 'within ' + data.range + ' years';
                status.textContent = data.count ? data.count + ' new events · ' + period + '. Save the ones you want to keep.' :
                    (data.has_more ? 'No new events in this batch. ' + (candidateCount ? 'Find 10 more' : 'Find 10 events again') + ' to check more candidates.' : 'No more events found ' + exhaustedPeriod + '. Try a different reference year.');
            }).catch(function (error) {
                if (current !== generation) return;
                status.textContent = message(error, 'Could not find events. Please try again.');
                if (error.response && error.response.status === 422) {
                    searchId = null;
                    more.hidden = true;
                    try { sessionStorage.removeItem(storageKey); } catch (e) {}
                }
            }).then(function () { if (current === generation) setBusy(false); });
    }
    form.addEventListener('submit', function (event) { event.preventDefault(); discover(candidateCount > 0 || !searchId); });
    more.addEventListener('click', function () { discover(false); });
    function resetSearch() {
        generation++;
        searchId = null;
        more.hidden = true;
        candidates.textContent = '';
        candidateCount = 0;
        candidateCounter.textContent = '0';
        resultsHeading.hidden = true;
        status.textContent = '';
        rememberSearch();
    }
    year.addEventListener('input', resetSearch);
    typeInputs.forEach(function (input) { input.addEventListener('change', resetSearch); });
    // A restored cursor can continue on Find, but More requires visible results.
    candidates.addEventListener('click', function (event) {
        var button = event.target.closest('.timeline-save');
        if (!button || button.disabled || busy) return;
        var card = button.closest('[data-candidate]');
        var current = generation;
        setBusy(true);
        button.textContent = 'Saving…';
        request(root.dataset.saveUrl, {search_id: searchId, source_id: card.dataset.candidate})
            .then(function (response) {
                var data = response.data;
                if (!saved.querySelector('[data-event-id="' + data.id + '"]')) {
                    saved.insertAdjacentHTML('beforeend', data.html);
                    arrangeSaved();
                }
                updateSavedCount(data.count);
                if (current === generation) {
                    button.dataset.saved = 'true';
                    button.textContent = 'Saved';
                    card.classList.add('is-saved');
                    card.querySelector('.timeline-save-status').textContent = 'Saved to timeline';
                }
            }).catch(function (error) {
                if (current !== generation) return;
                button.disabled = false;
                button.textContent = 'Save event';
                card.querySelector('.timeline-save-status').textContent = message(error, 'Could not save this event. Please try again.');
            }).then(function () { setBusy(false); });
    });
    saved.addEventListener('submit', function (event) {
        var removeForm = event.target.closest('.timeline-remove');
        if (!removeForm) return;
        event.preventDefault();
        if (busy) return;
        var row = removeForm.closest('[data-event-id]');
        var button = removeForm.querySelector('button[type="submit"]');
        var removeStatus = removeForm.querySelector('.timeline-remove-status');
        removeStatus.textContent = '';
        setBusy(true);
        button.textContent = 'Removing…';
        window.axios.delete(removeForm.action, {headers: {'X-CSRF-TOKEN': root.dataset.csrf, 'Accept': 'application/json'}})
            .then(function (response) {
                var data = response.data;
                row.remove();
                arrangeSaved();
                updateSavedCount(data.count);
                Array.prototype.forEach.call(candidates.querySelectorAll('[data-candidate]'), function (card) {
                    if (card.dataset.candidate !== data.source_id) return;
                    card.classList.remove('is-saved');
                    var saveButton = card.querySelector('.timeline-save');
                    saveButton.dataset.saved = 'false';
                    saveButton.textContent = 'Save event';
                    card.querySelector('.timeline-save-status').textContent = '';
                });
            }).catch(function () {
                removeStatus.textContent = 'Could not remove this event. Please try again.';
                button.textContent = 'Remove event';
            }).then(function () { setBusy(false); });
    });
})();
