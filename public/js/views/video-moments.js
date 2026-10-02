(function (root) {
    'use strict';

    function read(media) {
        try { return JSON.parse(media.getAttribute('data-video-moments') || '[]'); }
        catch (error) { return []; }
    }

    // Plyr inserts marker labels as HTML; escape even though the data is plain text.
    function escapeLabel(value) {
        return String(value).replace(/[&<>"']/g, function (character) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[character];
        });
    }

    function markerOptions(media) {
        var moments = read(media);
        if (!moments.length) return {};
        return {markers: {enabled: true, points: moments.map(function (moment) {
            // Plyr 3.7.8 excludes time=0; epsilon places its native marker at the start.
            return {time: Number(moment.start_time) || Number.EPSILON, label: escapeLabel(moment.title)};
        })}};
    }

    function activeAt(moments, time) {
        var active = null;
        moments.forEach(function (moment) {
            var start = Number(moment.start_time);
            var end = moment.end_time == null ? Infinity : Number(moment.end_time);
            if (time >= start && time < end && (!active || start > active.start_time)) active = moment;
        });
        return active;
    }

    function formatTime(seconds) {
        var total = Math.max(0, Math.floor(Number(seconds) || 0));
        var minutes = Math.floor(total / 60);
        var remainder = String(total % 60).padStart(2, '0');
        return total >= 3600 ? Math.floor(total / 3600) + ':' + String(minutes % 60).padStart(2, '0') + ':' + remainder : minutes + ':' + remainder;
    }

    function attach(player, media) {
        var moments = read(media);
        if (!moments.length) return;
        var doc = media.ownerDocument;
        var section = Array.from(doc.querySelectorAll('[data-moments-for]')).find(function (element) {
            return element.getAttribute('data-moments-for') === media.id;
        });
        if (!section || !player.elements || !player.elements.container) return;
        var container = player.elements.container;
        var aboutLayout = section.closest('.piece-about-media');
        var win = doc.defaultView || root;
        var heightObserver = null;
        function sizeList() {
            if (!aboutLayout || (player.fullscreen && player.fullscreen.active)) return;
            var height = container.getBoundingClientRect().height;
            // Hidden tabs and fullscreen must not replace the inline player's height.
            if (height > 0) section.style.setProperty('--piece-moments-video-height', height + 'px');
        }
        if (aboutLayout) {
            if (win.ResizeObserver) {
                heightObserver = new win.ResizeObserver(sizeList);
                heightObserver.observe(container);
            } else {
                win.addEventListener('resize', sizeList);
            }
        }
        var rows = Array.from(section.querySelectorAll('[data-moment-id]'));
        var active = null;
        var pending = null;
        var detailsOpen = false;
        var playbackStarted = !!player.playing;
        var previousTime = Number(player.currentTime) || 0;
        var overlay = doc.createElement('div');
        overlay.className = 'piece-moments__overlay';
        // Static markup only. Titles and commentary always use textContent below.
        overlay.innerHTML = '<button type="button" class="piece-moments__about" hidden aria-expanded="false">ⓘ About this section</button>' +
            '<div class="piece-moments__popover" role="dialog" hidden tabindex="-1">' +
            '<button type="button" class="piece-moments__close" aria-label="Close section commentary">×</button>' +
            '<div class="piece-moments__heading"><span class="piece-moments__timestamp"></span>' +
            '<h6 class="piece-moments__title"></h6></div><p class="piece-moments__comment"></p></div>';
        container.appendChild(overlay);
        var about = overlay.querySelector('.piece-moments__about');
        var popover = overlay.querySelector('.piece-moments__popover');
        var timestamp = overlay.querySelector('.piece-moments__timestamp');
        var title = overlay.querySelector('.piece-moments__title');
        var comment = overlay.querySelector('.piece-moments__comment');
        var close = overlay.querySelector('.piece-moments__close');
        popover.id = media.id + '-moment-popover';
        title.id = media.id + '-moment-title';
        popover.setAttribute('aria-labelledby', title.id);
        about.setAttribute('aria-controls', popover.id);

        function renderDetails() {
            about.hidden = !playbackStarted || !active;
            popover.hidden = !playbackStarted || !active || !detailsOpen;
            about.setAttribute('aria-expanded', popover.hidden ? 'false' : 'true');
        }
        function dismiss(restoreFocus) {
            detailsOpen = false;
            renderDetails();
            if (restoreFocus && !about.hidden) about.focus();
        }
        function synchronize(event) {
            var time = Number(player.currentTime) || 0;
            // Metadata, pre-play seeking and rejected play requests do not reveal the guide.
            if (event && event.type === 'playing') playbackStarted = true;
            // A new run starts collapsed; pausing/resuming elsewhere retains the preference.
            if (time === 0 && (previousTime > 0 || (event && event.type === 'play'))) detailsOpen = false;
            previousTime = time;
            var next = activeAt(moments, time);
            var hadFocus = popover.contains(doc.activeElement);
            if (next !== active) {
                active = next;
                if (active) {
                    timestamp.textContent = formatTime(active.start_time);
                    title.textContent = active.title;
                    comment.textContent = active.comment || '';
                    comment.hidden = !comment.textContent;
                }
            }
            // Gaps hide the panel without forgetting the viewer's reading preference.
            renderDetails();
            if (hadFocus && popover.hidden) {
                if (active) about.focus();
                else if (player.elements.buttons.play) {
                    var play = player.elements.buttons.play;
                    (Array.isArray(play) ? play[0] : play).focus();
                }
            }
            rows.forEach(function (row) {
                var selected = !!active && row.getAttribute('data-moment-id') === String(active.id);
                row.classList.toggle('is-active', selected);
                if (selected) row.setAttribute('aria-current', 'true');
                else row.removeAttribute('aria-current');
            });
        }
        function play() {
            var result = player.play();
            if (result && result.catch) result.catch(function () {});
        }
        function seek(moment, autoplay) {
            if (!player.duration) {
                pending = {moment: moment, autoplay: autoplay};
                if (autoplay) play(); // Also starts metadata loading on conservative browsers.
                return;
            }
            pending = null;
            player.currentTime = Number(moment.start_time);
            synchronize();
            if (autoplay) play();
        }
        function installMarkerLabels() {
            // Native 3.7.8 seek tooltips round time and miss fractional labels.
            // Give the native markers a title and keyboard access without replacing them.
            var points = moments.filter(function (moment) { return moment.start_time < player.duration; });
            container.querySelectorAll('.plyr__progress__marker').forEach(function (marker, index) {
                var moment = points[index];
                if (!moment || marker.getAttribute('data-moment-bound')) return;
                marker.setAttribute('data-moment-bound', 'true');
                marker.title = moment.title;
                marker.setAttribute('role', 'button');
                marker.setAttribute('tabindex', '0');
                marker.setAttribute('aria-label', moment.title);
                marker.addEventListener('click', function (event) {
                    event.stopPropagation();
                    seek(moment, false);
                });
                marker.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        event.stopPropagation();
                        seek(moment, false);
                    }
                });
            });
        }
        function metadata() {
            sizeList();
            installMarkerLabels();
            if (pending) seek(pending.moment, pending.autoplay);
            synchronize();
        }
        function listClick(event) {
            var row = event.target.closest('[data-moment-id]');
            if (!row || !section.contains(row)) return;
            var moment = moments.find(function (item) { return String(item.id) === row.getAttribute('data-moment-id'); });
            if (moment) seek(moment, true);
        }
        function outside(event) {
            // Seeking and playback controls keep the current reading preference.
            if (!popover.hidden && !container.contains(event.target) && !section.contains(event.target)) dismiss(false);
        }
        function keyboard(event) {
            if (event.key === 'Escape' && !popover.hidden) {
                event.preventDefault();
                event.stopPropagation();
                dismiss(true);
            }
        }
        overlay.addEventListener('click', function (event) { event.stopPropagation(); });
        // Editing controls must not trigger Plyr's keyboard shortcuts.
        overlay.addEventListener('keydown', function (event) { event.stopPropagation(); });
        about.addEventListener('click', function () {
            if (!popover.hidden) return dismiss(true);
            detailsOpen = true;
            renderDetails();
            close.focus();
        });
        close.addEventListener('click', function () { dismiss(true); });
        section.addEventListener('click', listClick);
        doc.addEventListener('click', outside);
        doc.addEventListener('keydown', keyboard, true);
        ['timeupdate', 'seeking', 'seeked', 'play', 'playing', 'pause', 'ended'].forEach(function (event) {
            player.on(event, synchronize);
        });
        ['ready', 'loadedmetadata', 'durationchange'].forEach(function (event) { player.on(event, metadata); });
        function cleanup() {
            if (heightObserver) heightObserver.disconnect();
            else if (aboutLayout) win.removeEventListener('resize', sizeList);
            if (aboutLayout) section.style.removeProperty('--piece-moments-video-height');
            section.removeEventListener('click', listClick);
            doc.removeEventListener('click', outside);
            doc.removeEventListener('keydown', keyboard, true);
            overlay.remove();
            if (player.elements && player.elements.original) player.elements.original.removeEventListener('destroyed', cleanup);
        }
        // Plyr emits destroyed on its restored original element after removing the container.
        if (player.elements.original) player.elements.original.addEventListener('destroyed', cleanup);
        metadata();
        return cleanup;
    }

    root.VideoMoments = {markerOptions: markerOptions, activeAt: activeAt, formatTime: formatTime, attach: attach};
}(typeof window !== 'undefined' ? window : globalThis));
