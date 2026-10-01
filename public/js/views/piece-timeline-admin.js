(function () {
    'use strict';
    var root = document.getElementById('piece-timeline-admin');
    if (!root) return;
    var form = document.getElementById('timeline-search');
    var year = document.getElementById('reference-year');
    var find = document.getElementById('timeline-find');
    var more = document.getElementById('timeline-more');
    var status = document.getElementById('timeline-search-status');
    var candidates = document.getElementById('timeline-candidates');
    var saved = document.getElementById('timeline-saved');
    var searchId = null;
    var busy = false;
    var generation = 0;
    var storageKey = 'pianolit.timeline.' + root.dataset.pieceId;
    try {
        var previous = JSON.parse(sessionStorage.getItem(storageKey));
        if (previous) { year.value = previous.year; searchId = previous.searchId; }
    } catch (e) {}

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
        Array.prototype.forEach.call(candidates.querySelectorAll('.timeline-save'), function (button) {
            button.disabled = value || button.dataset.saved === 'true';
        });
        form.setAttribute('aria-busy', value ? 'true' : 'false');
    }
    function discover(reset) {
        if (busy || !form.reportValidity()) return;
        if (reset) {
            searchId = null;
            candidates.textContent = '';
            more.hidden = true;
            generation++;
        }
        var current = generation;
        setBusy(true);
        status.textContent = 'Finding historical events…';
        request(root.dataset.discoverUrl, {reference_year: Number(year.value), search_id: searchId})
            .then(function (response) {
                if (current !== generation) return;
                var data = response.data;
                searchId = data.search_id;
                try { sessionStorage.setItem(storageKey, JSON.stringify({year: year.value, searchId: searchId})); } catch (e) {}
                candidates.insertAdjacentHTML('beforeend', data.html);
                more.hidden = !data.has_more;
                status.textContent = data.count ? data.count + ' candidates found within ' + data.range + ' years. Choose individual events to save.' :
                    (data.has_more ? 'No new candidates in this range. Find 10 more to search a wider period.' : 'No more candidates found within 40 years. You can start a new search with a different year.');
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
    form.addEventListener('submit', function (event) { event.preventDefault(); discover(true); });
    more.addEventListener('click', function () { discover(false); });
    year.addEventListener('input', function () {
        generation++;
        searchId = null;
        more.hidden = true;
        candidates.textContent = '';
        status.textContent = '';
    });
    // Restore a manually entered search after editing/removing a saved event.
    if (searchId) more.hidden = false;
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
                    Array.prototype.slice.call(saved.children).sort(function (a, b) {
                        return a.dataset.sort.localeCompare(b.dataset.sort);
                    }).forEach(function (node) { saved.appendChild(node); });
                }
                document.getElementById('timeline-empty').hidden = true;
                if (current === generation) {
                    button.dataset.saved = 'true';
                    button.textContent = 'Saved';
                    card.querySelector('.timeline-save-status').textContent = 'Added to this piece’s timeline.';
                }
            }).catch(function (error) {
                if (current !== generation) return;
                button.disabled = false;
                button.textContent = 'Save event';
                card.querySelector('.timeline-save-status').textContent = message(error, 'Could not save this event. Please try again.');
            }).then(function () { setBusy(false); });
    });
})();
