(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory;
    else factory(root.document, root);
}(typeof window !== 'undefined' ? window : this, function (doc, win) {
    'use strict';
    var page = doc.querySelector('[data-playlist-page]');
    if (!page) return;
    var list = page.querySelector('[data-playlist-tracks]');
    var player = page.querySelector('[data-playlist-player]');
    var status = page.querySelector('[data-playlist-status]');
    var audio = null, current = null, queue = [], shuffle = false, loop = 0, rate = 1, volume = 0.5, muted = false, busy = false;
    var started = false, previewStopped = false, selection = 0;
    var seek = player.querySelector('[data-seek]');
    var volumeInput = player.querySelector('[data-volume]');
    var volumeControl = player.querySelector('.playlist-player__volume');
    var deviceVolume = win.matchMedia ? win.matchMedia('(max-width: 991px), (pointer: coarse)') : null;
    var main = page.closest('main');
    function rows() { return Array.prototype.slice.call(list.querySelectorAll('[data-track]')); }
    function playable() { return rows().filter(function (row) { return !!row.getAttribute('data-audio'); }); }
    function time(seconds) {
        seconds = Number.isFinite(seconds) && seconds > 0 ? Math.floor(seconds) : 0;
        return Math.floor(seconds / 60) + ':' + ('0' + seconds % 60).slice(-2);
    }
    function message(value) { status.textContent = value; }
    function rebuildQueue() {
        queue = playable();
        if (shuffle) {
            for (var i = queue.length - 1; i > 0; i--) {
                var j = Math.floor(Math.random() * (i + 1));
                var item = queue[i]; queue[i] = queue[j]; queue[j] = item;
            }
            // Keep the current track first so enabling shuffle visits every remaining track.
            if (current && queue.indexOf(current) >= 0) queue.splice(0, 0, queue.splice(queue.indexOf(current), 1)[0]);
        }
    }
    function layout() {
        syncVolume();
        var menu = doc.getElementById('menu');
        var menuHeight = menu ? menu.getBoundingClientRect().height : 0;
        page.style.setProperty('--playlist-menu-height', menuHeight + 'px');
        if (main) main.style.marginBottom = (menuHeight + (player.hidden ? 0 : player.getBoundingClientRect().height) + 32) + 'px';
    }
    function syncVolume() {
        var useDevice = !!deviceVolume && deviceVolume.matches;
        volumeControl.hidden = useDevice;
        player.classList.toggle('playlist-player--device-volume', useDevice);
        // Mobile browsers cannot synchronize this slider with system volume.
        // Avoid a second attenuation/mute setting beneath the device controls.
        if (audio) { audio.volume = useDevice ? 1 : volume; audio.muted = useDevice ? false : muted; }
    }
    function icons(button, playing) {
        button.querySelector('[data-play-icon]').hidden = playing;
        button.querySelector('[data-pause-icon]').hidden = !playing;
    }
    function paint() {
        var playing = !!audio && !audio.paused && !audio.ended;
        rows().forEach(function (row) {
            var selected = row === current && started;
            row.classList.toggle('is-current', selected);
            row.classList.toggle('is-playing', selected && playing);
            icons(row.querySelector('[data-track-play]'), selected && playing);
            row.querySelector('[data-track-play]').setAttribute('aria-label', (selected && playing ? 'Pause ' : 'Play ') + row.getAttribute('data-title'));
        });
        var toggle = player.querySelector('[data-player-toggle]');
        toggle.disabled = !current;
        toggle.setAttribute('aria-label', playing ? 'Pause' : 'Play');
        icons(toggle, playing);
        var playAll = page.querySelector('[data-play-all]');
        playAll.setAttribute('aria-label', 'Play playlist');
    }
    function timeline() {
        var duration = audio && Number.isFinite(audio.duration) ? audio.duration : 0;
        var limit = current ? Number(current.getAttribute('data-preview')) : 0;
        seek.max = limit > 0 ? Math.min(limit, duration) : duration;
        seek.disabled = !duration;
        seek.value = audio ? Math.min(audio.currentTime || 0, Number(seek.max)) : 0;
        player.querySelector('[data-elapsed]').textContent = time(audio ? audio.currentTime : 0);
        player.querySelector('[data-duration]').textContent = time(duration);
        seek.setAttribute('aria-valuetext', time(Number(seek.value)) + ' of ' + time(Number(seek.max)));
        if (duration && current) current.querySelector('[data-track-duration]').textContent = time(duration);
    }
    function upgrade() {
        message('Your preview has ended. Go Premium to hear the full piece.');
        win.bootstrap.Modal.getOrCreateInstance(doc.getElementById('piece-upgrade-modal')).show();
    }
    function enforcePreview() {
        if (!audio || !current) return false;
        var limit = Number(current.getAttribute('data-preview'));
        if (limit <= 0 || (!previewStopped && audio.currentTime < limit)) return false;
        var first = !previewStopped;
        previewStopped = true;
        audio.pause();
        if (audio.currentTime > limit) audio.currentTime = limit;
        timeline(); paint();
        if (first) upgrade();
        return true;
    }
    function play() {
        if (!current) return;
        if (!audio) { select(current, true); return; }
        started = true;
        if (previewStopped || audio.ended) { previewStopped = false; audio.currentTime = 0; }
        var target = audio, request = selection;
        message(Number(current.getAttribute('data-preview')) > 0 ? 'Listen to a ' + current.getAttribute('data-preview') + '-second preview.' : '');
        var promise = target.play();
        if (promise && promise.catch) promise.catch(function () {
            if (target !== audio || request !== selection) return;
            target.pause(); paint();
            message('Audio could not start. Press play to try again, or open the piece with Go.');
        });
        paint();
    }
    function select(row, autoplay) {
        if (!row || !row.getAttribute('data-audio')) return;
        stopMetadata();
        selection++;
        if (audio) audio.pause();
        current = row; previewStopped = false; started = !!autoplay;
        player.hidden = false;
        player.querySelector('[data-player-title]').textContent = row.getAttribute('data-title');
        player.querySelector('[data-player-composer]').textContent = row.getAttribute('data-composer');
        player.querySelector('[data-player-artwork]').src = row.getAttribute('data-artwork');
        // Keep the same media element for every selection, including manual
        // changes, so iOS retains the playback permission granted by the tap.
        audio = audio || new win.Audio();
        var target = audio, request = selection;
        (target.playlistListeners || []).forEach(function (listener) { target.removeEventListener(listener.event, listener.handler); });
        target.playlistListeners = [];
        function listen(event, callback) {
            var handler = function () { if (target === audio && request === selection) callback(); };
            target.playlistListeners.push({event: event, handler: handler});
            target.addEventListener(event, handler);
        }
        target.preload = 'metadata'; syncVolume(); target.playbackRate = rate;
        ['play', 'playing', 'pause'].forEach(function (event) {
            listen(event, function () { enforcePreview(); paint(); });
        });
        ['timeupdate', 'seeking', 'seeked'].forEach(function (event) {
            listen(event, function () { enforcePreview(); timeline(); });
        });
        listen('loadedmetadata', timeline);
        listen('durationchange', timeline);
        listen('ended', function () {
            if (target !== audio || enforcePreview()) return;
            if (loop === 2) { target.currentTime = 0; play(); }
            else advance(1, true);
        });
        listen('error', function () {
            if (target !== audio || request !== selection) return;
            target.pause(); paint(); message('This recording is unavailable. Try another piece or open it with Go.');
        });
        target.src = row.getAttribute('data-audio');
        target.load(); timeline(); paint(); layout();
        if (autoplay) play();
    }
    function toggle() {
        if (audio && !audio.paused) audio.pause();
        else play();
    }
    function advance(direction, automatic) {
        var position = queue.indexOf(current) + direction;
        if (automatic && position >= queue.length && loop === 0) { paint(); return; }
        if (!queue.length) return;
        position = (position + queue.length) % queue.length;
        select(queue[position], true);
    }
    function renumber() {
        rows().forEach(function (row, index) { row.querySelector('[data-track-number]').textContent = index + 1; });
        var count = page.querySelector('[data-playlist-count]');
        var scoreCount = page.querySelector('[data-folder-score-count]');
        if (scoreCount) scoreCount.textContent = rows().filter(function (row) { return row.getAttribute('data-has-score') === 'true'; }).length;
        if (count) count.textContent = rows().length + (rows().length === 1 ? ' piece' : ' pieces');
        rebuildQueue();
        var available = queue.length > 0;
        ['[data-play-all]', '[data-shuffle]', '[data-loop]'].forEach(function (selector) { page.querySelector(selector).disabled = !available; });
        if (!available) { if (audio) audio.pause(); audio = null; current = null; player.hidden = true; }
        paint(); layout();
    }
    function restore(snapshot) { snapshot.forEach(function (row) { list.appendChild(row); }); renumber(); }
    function saveOrder(snapshot) {
        renumber();
        var url = list.getAttribute('data-url-reorder');
        if (!url) { message('Playback order updated for this visit.'); return; }
        busy = true;
        win.axios.patch(url, {ids: rows().map(function (row) { return Number(row.getAttribute('data-id')); })})
            .then(function () { message('Folder order saved.'); })
            .catch(function () { restore(snapshot); message('The order could not be saved. Your previous order has been restored.'); })
            .then(function () { busy = false; });
    }
    var activeDrag = null, dragFrame = null;
    function positionDrop() {
        var drag = activeDrag;
        if (!drag || !drag.moved) return;
        drag.row.style.transform = 'translateY(' + (drag.clientY - drag.offset) + 'px)';
        var candidates = rows().filter(function (row) { return row !== drag.row; });
        var before = candidates.filter(function (row) {
            var rect = row.getBoundingClientRect();
            return drag.clientY < rect.top + rect.height / 2;
        })[0];
        if (before) {
            if (drag.placeholder.nextSibling !== before) list.insertBefore(drag.placeholder, before);
        } else if (list.lastElementChild !== drag.placeholder) list.appendChild(drag.placeholder);
    }
    function scrollDrag() {
        if (!activeDrag || !activeDrag.moved) return;
        var bottom = player.hidden ? win.innerHeight : player.getBoundingClientRect().top;
        var delta = activeDrag.clientY < 60 ? -10 : (activeDrag.clientY > bottom - 40 ? 10 : 0);
        if (delta) { win.scrollBy(0, delta); positionDrop(); }
        dragFrame = win.requestAnimationFrame(scrollDrag);
    }
    function finishDrag(cancelled) {
        var drag = activeDrag;
        if (!drag) return;
        activeDrag = null;
        if (dragFrame !== null) { win.cancelAnimationFrame(dragFrame); dragFrame = null; }
        if (drag.moved) {
            list.insertBefore(drag.row, drag.placeholder);
            drag.placeholder.remove();
            drag.row.classList.remove('is-dragging');
            ['top', 'left', 'width', 'height', 'transform'].forEach(function (name) { drag.row.style.removeProperty(name); });
        }
        busy = false;
        if (cancelled) restore(drag.snapshot);
        else if (drag.moved && rows().some(function (row, index) { return row !== drag.snapshot[index]; })) saveOrder(drag.snapshot);
        drag.handle.focus({preventScroll: true});
    }
    // Moving a captured handle in the DOM releases pointer capture in browsers.
    // Keep the actual row stationary in the DOM while a placeholder moves, and
    // observe the gesture on the document so every move/drop still arrives.
    doc.addEventListener('pointermove', function (event) {
        var drag = activeDrag;
        if (!drag || event.pointerId !== drag.id) return;
        drag.clientY = event.clientY;
        if (!drag.moved && Math.abs(event.clientY - drag.y) < 5) return;
        event.preventDefault();
        if (!drag.moved) {
            var rect = drag.row.getBoundingClientRect();
            drag.moved = true;
            drag.placeholder = doc.createElement('div');
            drag.placeholder.className = 'playlist-track-placeholder';
            drag.placeholder.style.height = rect.height + 'px';
            drag.placeholder.setAttribute('aria-hidden', 'true');
            list.insertBefore(drag.placeholder, drag.row);
            drag.row.style.top = '0'; drag.row.style.left = rect.left + 'px';
            drag.row.style.width = rect.width + 'px'; drag.row.style.height = rect.height + 'px';
            drag.row.classList.add('is-dragging');
            dragFrame = win.requestAnimationFrame(scrollDrag);
        }
        positionDrop();
    }, {capture: true, passive: false});
    doc.addEventListener('pointerup', function (event) { if (activeDrag && event.pointerId === activeDrag.id) finishDrag(false); }, true);
    doc.addEventListener('pointercancel', function (event) { if (activeDrag && event.pointerId === activeDrag.id) finishDrag(true); }, true);
    doc.addEventListener('keydown', function (event) { if (activeDrag && event.key === 'Escape') { event.preventDefault(); finishDrag(true); } }, true);
    win.addEventListener('blur', function () { finishDrag(true); });
    rows().forEach(function (row) {
        row.querySelector('[data-track-play]').addEventListener('click', function () {
            if (row === current && audio) toggle(); else select(row, true);
        });
        var favorite = row.querySelector('[data-playlist-favorite]');
        if (favorite) favorite.addEventListener('click', function () {
            if (busy) return;
            busy = true; favorite.disabled = true;
            win.axios.post(favorite.getAttribute('data-url')).then(function (response) {
                var saved = !!response.data.status;
                favorite.setAttribute('aria-pressed', saved ? 'true' : 'false');
                favorite.setAttribute('data-favorited', saved ? 'true' : 'false');
                favorite.querySelector('i').classList.toggle('icon-filled', saved);
                if (list.getAttribute('data-url-reorder') && !saved) {
                    var wasCurrent = row === current;
                    var next = queue[queue.indexOf(row) + 1] || queue[0];
                    var wasPlaying = !!audio && !audio.paused;
                    if (wasCurrent && audio) audio.pause();
                    row.remove(); renumber();
                    if (wasCurrent && queue.length) select(next === row ? queue[0] : next, wasPlaying);
                    message('Piece removed from this folder.');
                    if (!rows().length) {
                        var empty = doc.createElement('p'); empty.className = 'playlist-empty'; empty.textContent = 'No pieces here yet.'; list.appendChild(empty);
                    }
                } else message(saved ? 'Piece saved to My favorites.' : 'Piece removed from My favorites.');
            }).catch(function () { message('Your favorite could not be updated. Please try again.'); })
                .then(function () { busy = false; favorite.disabled = false; });
        });
        var handle = row.querySelector('[data-track-handle]');
        handle.addEventListener('keydown', function (event) {
            if (busy || (event.key !== 'ArrowUp' && event.key !== 'ArrowDown')) return;
            event.preventDefault();
            var snapshot = rows(), index = snapshot.indexOf(row), neighbor = snapshot[index + (event.key === 'ArrowUp' ? -1 : 1)];
            if (!neighbor) return;
            list.insertBefore(row, event.key === 'ArrowUp' ? neighbor : neighbor.nextSibling);
            saveOrder(snapshot); handle.focus();
        });
        handle.addEventListener('pointerdown', function (event) {
            if (busy || activeDrag || event.button !== 0 || event.isPrimary === false) return;
            event.preventDefault();
            var rect = row.getBoundingClientRect();
            activeDrag = {row: row, handle: handle, snapshot: rows(), y: event.clientY, clientY: event.clientY, offset: event.clientY - rect.top, moved: false, id: event.pointerId};
            busy = true;
            handle.focus({preventScroll: true});
        });
        handle.addEventListener('dragstart', function (event) { event.preventDefault(); });
    });
    page.querySelector('[data-play-all]').addEventListener('click', function () { select(queue[0], true); });
    page.querySelector('[data-shuffle]').addEventListener('click', function () {
        shuffle = !shuffle; this.setAttribute('aria-pressed', shuffle ? 'true' : 'false'); this.classList.toggle('active', shuffle); rebuildQueue();
        message(shuffle ? 'Shuffle on.' : 'Shuffle off.');
    });
    page.querySelector('[data-loop]').addEventListener('click', function () {
        loop = (loop + 1) % 3;
        this.setAttribute('aria-pressed', loop ? 'true' : 'false');
        this.classList.toggle('active', !!loop);
        this.querySelector('span:last-child').textContent = ['Loop: Off', 'Loop: All', 'Loop: One'][loop];
    });
    player.querySelector('[data-player-toggle]').addEventListener('click', toggle);
    player.querySelector('[data-next]').addEventListener('click', function () { advance(1, false); });
    player.querySelector('[data-previous]').addEventListener('click', function () {
        if (audio && audio.currentTime > 3) { previewStopped = false; audio.currentTime = 0; play(); }
        else advance(-1, false);
    });
    seek.addEventListener('input', function () { if (audio) { audio.currentTime = Number(seek.value); enforcePreview(); timeline(); } });
    volumeInput.addEventListener('input', function () {
        if (deviceVolume && deviceVolume.matches) return;
        volume = Number(volumeInput.value); muted = false;
        if (audio) { audio.volume = volume; audio.muted = false; }
        player.querySelector('[data-mute]').setAttribute('aria-pressed', 'false'); player.querySelector('[data-mute]').setAttribute('aria-label', 'Mute');
    });
    player.querySelector('[data-mute]').addEventListener('click', function () { if (deviceVolume && deviceVolume.matches) return; muted = !muted; if (audio) audio.muted = muted; this.setAttribute('aria-pressed', muted ? 'true' : 'false'); this.setAttribute('aria-label', muted ? 'Unmute' : 'Mute'); });
    Array.prototype.forEach.call(player.querySelectorAll('[data-speed]'), function (button) {
        button.addEventListener('click', function () {
            rate = Number(button.getAttribute('data-speed')); if (audio) audio.playbackRate = rate;
            player.querySelector('[data-speed-label]').textContent = (rate % 1 === 0 ? rate.toFixed(1) : rate) + '×';
            Array.prototype.forEach.call(player.querySelectorAll('[data-speed]'), function (item) {
                var selected = item === button; item.classList.toggle('active', selected); item.setAttribute('aria-pressed', selected ? 'true' : 'false'); item.querySelector('[data-speed-check]').hidden = !selected;
            });
        });
    });
    win.addEventListener('resize', layout);
    if (deviceVolume) {
        if (deviceVolume.addEventListener) deviceVolume.addEventListener('change', layout);
        else if (deviceVolume.addListener) deviceVolume.addListener(layout);
    }
    win.addEventListener('pagehide', function () { finishDrag(true); stopMetadata(); if (audio) audio.pause(); });
    if (win.ResizeObserver) { var resize = new win.ResizeObserver(layout); resize.observe(player); var menu = doc.getElementById('menu'); if (menu) resize.observe(menu); }
    player.hidden = true;
    renumber();
    // Use one retained metadata element, and release it before playback. Loading
    // more recordings in the background must never compete with the iOS player.
    var metadataQueue = [], metadataAudio = null, metadataCancel = null, metadataStopped = false, observer = null;
    function stopMetadata() {
        metadataStopped = true; metadataQueue = [];
        if (observer) observer.disconnect();
        if (metadataCancel) metadataCancel();
        metadataAudio = null;
    }
    function probe() {
        if (metadataStopped || metadataCancel || !metadataQueue.length) return;
        var row = metadataQueue.shift(), media = metadataAudio || new win.Audio(), finished = false;
        metadataAudio = media; media.preload = 'metadata';
        var timer;
        function cleanup() {
            if (finished) return; finished = true;
            win.clearTimeout(timer);
            media.removeEventListener('loadedmetadata', loaded);
            media.removeEventListener('durationchange', loaded);
            media.removeEventListener('error', done);
            metadataCancel = null;
            media.removeAttribute('src'); media.load();
        }
        function loaded() {
            if (finished || metadataStopped) return;
            if (Number.isFinite(media.duration) && media.duration > 0) {
                row.querySelector('[data-track-duration]').textContent = time(media.duration);
                done();
            }
        }
        function done() { if (finished) return; cleanup(); probe(); }
        metadataCancel = cleanup;
        timer = win.setTimeout(done, 10000);
        media.addEventListener('loadedmetadata', loaded); media.addEventListener('durationchange', loaded); media.addEventListener('error', done);
        media.src = row.getAttribute('data-audio'); media.load();
    }
    if (win.IntersectionObserver) {
        observer = new win.IntersectionObserver(function (entries) {
            if (metadataStopped) return;
            entries.forEach(function (entry) { if (entry.isIntersecting) { observer.unobserve(entry.target); metadataQueue.push(entry.target); } }); probe();
        }, {rootMargin: '150px'});
        playable().forEach(function (row) { observer.observe(row); });
    }
    return {time: time};
}));
