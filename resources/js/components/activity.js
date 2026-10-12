(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory;
    else factory(root, root.pianoActivityConfig);
}(typeof window !== 'undefined' ? window : this, function (win, config) {
    'use strict';
    if (!config || win.PianoActivity) return;
    var doc = win.document, sequence = 0, sending = false, retryAt = 0, failures = 0;
    var storageKey = 'pianolit.activity.outbox.v1', outbox = [], mediaStates = [];
    var visible = doc.visibilityState !== 'hidden', visibleSince = clock(), visibleTotal = 0;
    function clock() { return Math.max(0, Math.round(win.performance.now())); }
    function uuid() {
        var bytes = new Uint8Array(16); win.crypto.getRandomValues(bytes);
        bytes[6] = (bytes[6] & 15) | 64; bytes[8] = (bytes[8] & 63) | 128;
        var hex = Array.prototype.map.call(bytes, function (b) { return ('0' + b.toString(16)).slice(-2); }).join('');
        return hex.slice(0, 8) + '-' + hex.slice(8, 12) + '-' + hex.slice(12, 16) + '-' + hex.slice(16, 20) + '-' + hex.slice(20);
    }
    function persist() {
        try { win.sessionStorage.setItem(storageKey, JSON.stringify(outbox)); } catch (error) { /* The in-memory queue still works. */ }
    }
    try {
        var saved = JSON.parse(win.sessionStorage.getItem(storageKey) || '[]');
        if (Array.isArray(saved)) outbox = saved.filter(function (batch) {
            return batch && typeof batch.page_token === 'string' && Array.isArray(batch.events) && batch.events.length <= 50;
        }).slice(-100);
    } catch (error) { /* Private browsing may disable storage. */ }
    function record(type, metadata) {
        if (clock() > 86400000) return; // The page token expires after 24 hours.
        var batch = outbox[outbox.length - 1];
        if (!batch || batch.page_token !== config.pageToken || batch.events.length >= 20 || batch === sending) {
            batch = {page_token: config.pageToken, events: []}; outbox.push(batch);
        }
        batch.events.push({id: uuid(), type: type, elapsed_ms: clock(), sequence: ++sequence, metadata: metadata || {}});
        // Bound prolonged offline storage, retaining recent interaction history.
        if (outbox.length > 100) outbox.shift();
        persist();
    }
    function flush(beacon) {
        if (!outbox.length) return;
        var batch = outbox[0];
        var payload = JSON.stringify({page_token: batch.page_token, events: batch.events, _token: config.csrfToken});
        if (beacon && win.navigator.sendBeacon) {
            // Keep queued data until acknowledged by fetch on this or the next page.
            // Re-delivery is safe because event IDs are unique in the database.
            var budget = 48000;
            outbox.slice().reverse().some(function (pending) {
                var body = JSON.stringify({page_token: pending.page_token, events: pending.events, _token: config.csrfToken});
                if (body.length * 3 > budget) return true;
                budget -= body.length * 3;
                try { win.navigator.sendBeacon(config.endpoint, new win.Blob([body], {type: 'application/json'})); } catch (error) {}
                return false;
            });
            return;
        }
        if (sending || Date.now() < retryAt) return;
        sending = batch;
        win.fetch(config.endpoint, {method: 'POST', credentials: 'same-origin', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': config.csrfToken}, body: payload, keepalive: true})
            .then(function (response) {
                if (response.ok || [403, 419, 422].indexOf(response.status) !== -1) {
                    // Invalid/expired tokens cannot be transferred to a different account.
                    var index = outbox.indexOf(batch); if (index !== -1) outbox.splice(index, 1);
                    failures = 0; retryAt = 0; persist();
                } else throw new Error('Activity delivery failed');
            }).catch(function () { failures++; retryAt = Date.now() + Math.min(30000, 1000 * Math.pow(2, failures)); })
            .then(function () { sending = false; });
    }
    function visibleMs() { return Math.round(visibleTotal + (visible ? clock() - visibleSince : 0)); }
    function lifecycle(type) { record(type, {visible_ms: visibleMs()}); }
    function safeUrl(value) {
        try {
            var url = new win.URL(value, win.location.href);
            if (url.protocol !== 'http:' && url.protocol !== 'https:') return '';
            var query = new win.URLSearchParams();
            url.searchParams.forEach(function (value, key) {
                if (/^(search|catalogue|sort|level|mood|tag|period|technique|length|composers|country|video_only|score_only|audio_only|page)$/.test(key) || /^(filters|facets)\[/.test(key)) query.append(key, value.slice(0, 300));
            });
            var path = url.pathname.replace(/(\/password\/reset\/)[^/]+/i, '$1[redacted]').replace(/(\/email\/verify\/)[^/]+\/[^/]+/i, '$1[redacted]');
            return (url.origin + path + (query.toString() ? '?' + query : '')).slice(0, 2000);
        } catch (error) { return ''; }
    }
    function label(element) {
        return (element.getAttribute('data-activity-label') || element.getAttribute('aria-label') || element.getAttribute('title') || element.textContent || element.id || element.tagName).trim().replace(/\s+/g, ' ').slice(0, 160);
    }
    function selectedFilters() {
        var filters = {};
        doc.querySelectorAll('[data-search-facet]').forEach(function (group) {
            filters[group.getAttribute('data-search-facet')] = Array.prototype.map.call(group.querySelectorAll('[data-search-value][aria-pressed="true"]'), function (button) { return button.getAttribute('data-search-value'); });
        });
        doc.querySelectorAll('#search-controls-form input:checked, #search-controls-form input[type=range]').forEach(function (input) {
            filters[input.name || input.getAttribute('aria-label')] = input.value;
        });
        return filters;
    }
    doc.addEventListener('click', function (event) {
        var element = event.target.closest && event.target.closest('a,button,summary,[role="button"],[data-url],[data-search-value],[data-search-apply],[data-erase],.tag,.recent-query');
        if (!element || element.closest('[data-activity-ignore]')) return;
        if (element.hasAttribute('data-search-value') || element.classList.contains('tag')) {
            record('filter_change', {control: element.closest('[data-search-facet]') ? element.closest('[data-search-facet]').getAttribute('data-search-facet') : 'tag', value: element.getAttribute('data-search-value') || element.getAttribute('data-name'), selected: element.getAttribute('aria-pressed') !== 'true'});
        } else if (element.hasAttribute('data-search-apply')) {
            // Read after the existing control's handler commits its draft.
            win.setTimeout(function () { record('filters_apply', {filters: selectedFilters()}); }, 0);
        } else {
            var choice = element.getAttribute('data-choice');
            var piece = element.getAttribute('data-select-level') || element.getAttribute('data-select-piece') || element.getAttribute('data-play');
            record('click', {label: label(element), control: element.id || element.getAttribute('data-action') || element.getAttribute('data-submit') || (choice !== null ? 'match_choice' : piece ? 'match_piece' : element.tagName.toLowerCase()), target: safeUrl(element.getAttribute('href') || element.getAttribute('data-url') || ''), value: choice === null ? undefined : choice.slice(0, 300), piece_id: piece && /^\d+$/.test(piece) ? Number(piece) : config.pieceId});
        }
    }, true);
    doc.addEventListener('change', function (event) {
        var input = event.target;
        if (!input.closest('#search-controls-form') || !/^(checkbox|radio|range)$/.test(input.type)) return;
        record('filter_change', {control: input.name || input.getAttribute('aria-label'), value: input.value, selected: input.checked});
    }, true);
    function formToken(form) {
        var url; try { url = new win.URL(form.action || win.location.href, win.location.href); } catch (error) { return; }
        if (url.origin !== win.location.origin || (form.method || 'get').toLowerCase() === 'get') return;
        var input = form.querySelector('[name="_activity_page"]');
        if (!input) { input = doc.createElement('input'); input.type = 'hidden'; input.name = '_activity_page'; form.appendChild(input); }
        input.value = config.pageToken;
    }
    doc.querySelectorAll('form').forEach(formToken);
    doc.addEventListener('submit', function (event) {
        var form = event.target; formToken(form);
        var search = form.querySelector('[name="search"]');
        record(search ? 'search_submit' : 'form_submit', {control: form.id || 'form', target: safeUrl(form.action || win.location.href), search: search ? search.value.slice(0, 300) : undefined});
        flush(true);
    }, true);
    doc.addEventListener('toggle', function (event) {
        if (event.target.tagName !== 'DETAILS') return;
        var summary = event.target.querySelector('summary');
        record(event.target.open ? 'section_open' : 'section_close', {section: summary ? label(summary) : event.target.id});
    }, true);
    if (win.jQuery) {
        win.jQuery(doc).on('shown.bs.tab shown.bs.modal shown.bs.offcanvas', function (event) { record('tab_open', {section: event.target.id || event.target.getAttribute('aria-label') || event.target.tagName.toLowerCase()}); });
        win.jQuery.ajaxPrefilter(function (options) {
            if (new win.URL(options.url, win.location.href).origin === win.location.origin) {
                options.headers = Object.assign({}, options.headers, {'X-Activity-Page': config.pageToken});
            }
        });
    }
    if (win.axios) win.axios.interceptors.request.use(function (request) {
        try {
            var base = new win.URL(request.baseURL || win.location.href, win.location.href);
            if (new win.URL(request.url, base).origin === win.location.origin) { request.headers = request.headers || {}; request.headers['X-Activity-Page'] = config.pageToken; }
        } catch (error) { /* Analytics must not interfere with the request. */ }
        return request;
    });
    function mediaState(media, context) {
        var state = mediaStates.filter(function (item) { return item.media === media; })[0];
        if (!state) {
            state = {media: media, context: context || {}, played: 0, since: null, lastEvent: null}; mediaStates.push(state);
            ['playing', 'pause', 'ended', 'seeking', 'seeked', 'waiting', 'error'].forEach(function (name) { media.addEventListener(name, function (event) { mediaEvent(media, event); }, true); });
        }
        if (context) {
            if (JSON.stringify(state.context) !== JSON.stringify(context)) {
                if (state.since !== null) record('media_pause', Object.assign(mediaData(state), {reason: 'source_changed'}));
                // Native pause events can be queued until after a shared Audio
                // element has already been assigned to its next piece.
                state.ignoreQueuedPause = true; state.played = 0; state.since = null;
            }
            state.context = context;
        }
        return state;
    }
    function mediaData(state) {
        var media = state.media;
        return Object.assign({media_kind: (media.tagName || 'audio').toLowerCase(), media_id: media.id || (media.closest && media.closest('[id]') ? media.closest('[id]').id : 'audio'), piece_id: config.pieceId, position_seconds: Number.isFinite(media.currentTime) ? media.currentTime : 0, duration_seconds: Number.isFinite(media.duration) ? media.duration : null, played_seconds: (state.played + (state.since === null ? 0 : clock() - state.since)) / 1000, playback_rate: media.playbackRate || 1}, state.context);
    }
    function mediaEvent(media, event) {
        var state = mediaState(media);
        if (state.lastEvent === event) return;
        state.lastEvent = event;
        if (event.type === 'playing') {
            state.ignoreQueuedPause = false;
            if (state.since === null) { state.since = clock(); record('media_play', mediaData(state)); }
            return;
        }
        if (event.type === 'pause' && state.ignoreQueuedPause) { state.ignoreQueuedPause = false; return; }
        if (['pause', 'ended', 'seeking', 'waiting', 'error'].indexOf(event.type) !== -1 && state.since !== null) {
            state.played += clock() - state.since; state.since = null;
        }
        var types = {pause: 'media_pause', ended: 'media_end', seeked: 'media_seek', error: 'media_error'};
        if (types[event.type]) record(types[event.type], mediaData(state));
        if (event.type === 'seeked' && !media.paused && !media.seeking) state.since = clock();
    }
    function stopMedia(media, reason) {
        var state = mediaStates.filter(function (item) { return item.media === media; })[0];
        if (!state || state.since === null) return;
        state.played += clock() - state.since; state.since = null;
        record('media_pause', Object.assign(mediaData(state), {reason: reason || 'stop'}));
        state.ignoreQueuedPause = true;
    }
    ['playing', 'pause', 'ended', 'seeking', 'seeked', 'waiting', 'error'].forEach(function (name) {
        doc.addEventListener(name, function (event) { if (/^(AUDIO|VIDEO)$/.test(event.target.tagName)) mediaEvent(event.target, event); }, true);
    });
    win.PianoActivity = {record: record, media: mediaState, stopMedia: stopMedia, flush: flush};
    lifecycle('page_ready');
    doc.addEventListener('visibilitychange', function () {
        if (visible) visibleTotal += clock() - visibleSince;
        visible = doc.visibilityState !== 'hidden'; visibleSince = clock();
        lifecycle(visible ? 'page_visible' : 'page_hidden'); flush(!visible);
    });
    win.addEventListener('pagehide', function () {
        mediaStates.forEach(function (state) { if (state.since !== null) record('media_progress', Object.assign(mediaData(state), {reason: 'page_leave'})); });
        lifecycle('page_leave'); flush(true);
    });
    win.addEventListener('pageshow', function (event) { if (event.persisted) { lifecycle('page_restore'); flush(); } });
    win.addEventListener('online', function () { retryAt = 0; flush(); });
    win.setInterval(function () { flush(); }, 3000);
    win.setInterval(function () {
        if (visible) lifecycle('page_checkpoint');
        mediaStates.forEach(function (state) { if (state.since !== null) record('media_progress', mediaData(state)); });
    }, 15000);
    flush();
}));
