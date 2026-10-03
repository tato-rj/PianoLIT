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
        var moments = [], entries = [], expanded = false, markerLimit = -1;
        function clear(element) { while (element.firstChild) element.removeChild(element.firstChild); }
        function focusToggle() { toggle.focus(); }
        function expand(value) {
            expanded = value && moments.length > 0;
            if (!expanded && panel.contains(doc.activeElement)) focusToggle();
            panel.hidden = !expanded;
            toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            toggle.classList.toggle('active', expanded);
            player.classList.toggle('playlist-player--sections-open', expanded);
            callbacks.layout();
        }
        toggle.addEventListener('click', function () { expand(!expanded); });
        panel.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') return;
            event.preventDefault(); event.stopPropagation();
            expand(false);
        });
        function select(row) {
            if (panel.contains(doc.activeElement) || markers.contains(doc.activeElement) || toggle === doc.activeElement) player.querySelector('[data-player-toggle]').focus();
            try { moments = JSON.parse(row.getAttribute('data-audio-moments') || '[]'); } catch (error) { moments = []; }
            moments = Array.isArray(moments) ? moments.filter(function (moment) {
                return moment && Number.isFinite(Number(moment.start_time)) && Number(moment.start_time) >= 0 && typeof moment.title === 'string';
            }) : [];
            entries = []; markerLimit = -1;
            clear(list); clear(markers);
            toggle.hidden = !moments.length;
            player.classList.toggle('playlist-player--has-sections', !!moments.length);
            moments.forEach(function (moment) {
                var row = template.content.firstElementChild.cloneNode(true);
                var seek = row.querySelector('[data-section-seek]');
                row.querySelector('[data-section-time]').textContent = guide.formatTime(moment.start_time);
                row.querySelector('[data-section-title]').textContent = moment.title;
                seek.setAttribute('aria-label', 'Play ' + moment.title + ' at ' + guide.formatTime(moment.start_time));
                seek.addEventListener('click', function () { callbacks.seek(Number(moment.start_time), true); });
                entries.push({moment: moment, row: row, seek: seek}); list.appendChild(row);
            });
            expand(false);
        }
        function synchronize(media) {
            var active = guide.activeAt(moments, media ? Number(media.currentTime) || 0 : 0);
            entries.forEach(function (entry) {
                var selected = entry.moment === active;
                entry.row.classList.toggle('is-active', selected);
                if (selected) entry.row.setAttribute('aria-current', 'true'); else entry.row.removeAttribute('aria-current');
                entry.row.querySelector('[data-section-play]').hidden = !selected;
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
        }
        return {select: select, synchronize: synchronize};
    }
    return {create: create};
}));
