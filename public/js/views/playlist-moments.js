(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory;
    else root.PlaylistMoments = factory();
}(typeof window !== 'undefined' ? window : this, function () {
    'use strict';
    function create(doc, player, guide, callbacks) {
        var toggle = player.querySelector('[data-sections-toggle]');
        var panel = player.querySelector('[data-sections-panel]');
        var list = panel.querySelector('[data-sections-list]');
        var template = player.querySelector('[data-section-template]');
        var markers = player.querySelector('[data-section-markers]');
        var commentary = panel.querySelector('[data-section-commentary]');
        var close = panel.querySelector('[data-section-comment-close]');
        var moments = [], entries = [], expanded = false, detailsOpen = false, detailsMoment = null;
        var active = null, media = null, previousTime = 0, markerLimit = -1;
        function clear(element) { while (element.firstChild) element.removeChild(element.firstChild); }
        function focusToggle() { toggle.focus(); }
        function showCommentary() {
            var nearEnd = media && Number.isFinite(media.duration) && media.duration > 0 && media.currentTime >= Math.max(0, media.duration - 2);
            var hidden = !expanded || !detailsOpen || !detailsMoment || nearEnd;
            if (hidden && commentary.contains(doc.activeElement)) {
                var entry = entries.find(function (entry) { return entry.moment === detailsMoment; });
                (entry && expanded ? entry.about : toggle).focus();
            }
            var changed = commentary.hidden !== hidden;
            commentary.hidden = hidden;
            if (!hidden) {
                panel.querySelector('[data-section-comment-time]').textContent = guide.formatTime(detailsMoment.start_time);
                panel.querySelector('[data-section-comment-title]').textContent = detailsMoment.title;
                var comment = panel.querySelector('[data-section-comment-text]');
                comment.textContent = detailsMoment.comment || ''; comment.hidden = !comment.textContent;
            }
            entries.forEach(function (entry) { entry.about.setAttribute('aria-expanded', !hidden && entry.moment === detailsMoment ? 'true' : 'false'); });
            if (changed) callbacks.layout();
        }
        function expand(value) {
            expanded = value && moments.length > 0;
            if (!expanded && panel.contains(doc.activeElement)) focusToggle();
            panel.hidden = !expanded;
            toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            toggle.classList.toggle('active', expanded);
            player.classList.toggle('playlist-player--sections-open', expanded);
            showCommentary(); callbacks.layout();
        }
        toggle.addEventListener('click', function () { expand(!expanded); });
        close.addEventListener('click', function () { detailsOpen = false; showCommentary(); });
        panel.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') return;
            event.preventDefault(); event.stopPropagation();
            if (!commentary.hidden) { detailsOpen = false; showCommentary(); }
            else expand(false);
        });
        function select(row) {
            if (panel.contains(doc.activeElement) || markers.contains(doc.activeElement) || toggle === doc.activeElement) player.querySelector('[data-player-toggle]').focus();
            try { moments = JSON.parse(row.getAttribute('data-audio-moments') || '[]'); } catch (error) { moments = []; }
            moments = Array.isArray(moments) ? moments.filter(function (moment) {
                return moment && Number.isFinite(Number(moment.start_time)) && Number(moment.start_time) >= 0 && typeof moment.title === 'string';
            }) : [];
            entries = []; active = null; media = null; previousTime = 0; markerLimit = -1; detailsOpen = false; detailsMoment = null;
            clear(list); clear(markers);
            toggle.hidden = !moments.length;
            player.classList.toggle('playlist-player--has-sections', !!moments.length);
            moments.forEach(function (moment) {
                var row = template.content.firstElementChild.cloneNode(true);
                var seek = row.querySelector('[data-section-seek]');
                var about = row.querySelector('[data-section-about]');
                row.querySelector('[data-section-time]').textContent = guide.formatTime(moment.start_time);
                row.querySelector('[data-section-title]').textContent = moment.title;
                seek.setAttribute('aria-label', 'Play ' + moment.title + ' at ' + guide.formatTime(moment.start_time));
                about.setAttribute('aria-label', 'About ' + moment.title);
                seek.addEventListener('click', function () { callbacks.seek(Number(moment.start_time), true); });
                about.addEventListener('click', function () {
                    detailsOpen = !(detailsOpen && detailsMoment === moment && !commentary.hidden);
                    detailsMoment = moment; showCommentary();
                    if (!commentary.hidden) {
                        close.focus();
                        // Show the commentary body, not only its focused close
                        // button, when the phone's section list must scroll.
                        panel.scrollTop = panel.scrollHeight;
                    }
                });
                entries.push({moment: moment, row: row, seek: seek, about: about}); list.appendChild(row);
            });
            expand(false);
        }
        function synchronize(target, event) {
            media = target;
            var time = media ? Number(media.currentTime) || 0 : 0;
            if (time === 0 && (previousTime > 0 || event === 'play')) { detailsOpen = false; detailsMoment = null; }
            previousTime = time;
            var next = guide.activeAt(moments, time);
            // Reading the active section follows transitions and survives gaps.
            if (next !== active && (!detailsMoment || detailsMoment === active)) detailsMoment = next;
            active = next;
            entries.forEach(function (entry) {
                var selected = entry.moment === active;
                entry.row.classList.toggle('is-active', selected);
                if (selected) entry.row.setAttribute('aria-current', 'true'); else entry.row.removeAttribute('aria-current');
                entry.row.querySelector('[data-section-play]').hidden = !selected;
                entry.about.querySelector('[data-section-about-label]').hidden = !selected;
                entry.seek.disabled = !!media && Number.isFinite(media.duration) && Number(entry.moment.start_time) >= media.duration;
            });
            var limit = Number(player.querySelector('[data-seek]').max) || 0;
            if (limit !== markerLimit) {
                markerLimit = limit; clear(markers);
                moments.filter(function (moment) { return Number(moment.start_time) < limit; }).forEach(function (moment) {
                    var marker = doc.createElement('button');
                    marker.type = 'button'; marker.className = 'playlist-player__marker btn-raw';
                    marker.style.left = (Number(moment.start_time) / limit * 100) + '%';
                    marker.title = moment.title; marker.setAttribute('aria-label', 'Seek to ' + moment.title + ' at ' + guide.formatTime(moment.start_time));
                    marker.addEventListener('click', function () { callbacks.seek(Number(moment.start_time), false); });
                    markers.appendChild(marker);
                });
            }
            showCommentary();
        }
        return {select: select, synchronize: synchronize};
    }
    return {create: create};
}));
