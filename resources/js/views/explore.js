(function (root) {
    'use strict';

    function install(document, createAudio, timers) {
        let active = null;
        function render(row, playing, loading, message) {
            const button = row.querySelector('[data-example-toggle]');
            const title = row.querySelector('[data-example-title]').textContent;
            button.setAttribute('aria-pressed', playing ? 'true' : 'false');
            button.setAttribute('aria-busy', loading ? 'true' : 'false');
            button.setAttribute('aria-label', playing ? 'Pause ' + title : loading ? 'Cancel loading example' : 'Hear an example');
            row.querySelector('[data-example-play]').hidden = playing || loading;
            row.querySelector('[data-example-pause]').hidden = !playing && !loading;
            row.querySelector('[data-example-idle]').hidden = playing;
            row.querySelector('[data-example-idle]').textContent = loading ? 'Loading example…' : 'Hear an example';
            row.querySelector('[data-example-identity]').hidden = !playing;
            row.querySelector('[data-example-go]').hidden = !playing;
            const status = row.querySelector('[data-example-status]');
            status.textContent = message || '';
            status.hidden = !message;
        }
        function stop(state, message) {
            if (!state || active !== state) return;
            active = null;
            timers.clearTimeout(state.timer);
            state.audio.pause();
            try { state.audio.currentTime = 0; } catch (error) { /* Metadata may not have loaded. */ }
            render(state.row, false, false, message);
        }
        function start(row) {
            stop(active);
            const audio = createAudio();
            const state = {row: row, audio: audio, timer: null};
            active = state;
            const preview = Number(row.getAttribute('data-preview'));
            const restricted = Number.isFinite(preview) && preview > 0;
            function guard() {
                if (active !== state) return false;
                if (restricted && audio.currentTime >= preview) {
                    stop(state, 'Preview ended.');
                    return false;
                }
                return true;
            }
            function scheduleLimit() {
                timers.clearTimeout(state.timer);
                if (!restricted || audio.paused || !guard()) return;
                state.timer = timers.setTimeout(function () {
                    if (guard()) scheduleLimit();
                }, Math.max(25, (preview - audio.currentTime) * 1000 / (audio.playbackRate || 1)));
            }
            audio.preload = 'none';
            audio.src = row.getAttribute('data-audio');
            audio.addEventListener('playing', function () {
                if (active !== state) { audio.pause(); return; }
                if (!guard()) return;
                render(row, true, false);
                scheduleLimit();
            });
            ['timeupdate', 'seeking', 'seeked'].forEach(function (event) { audio.addEventListener(event, guard); });
            audio.addEventListener('ratechange', scheduleLimit);
            audio.addEventListener('pause', function () { stop(state); });
            audio.addEventListener('ended', function () { stop(state); });
            audio.addEventListener('error', function () { stop(state, 'This example could not be played. Please try again.'); });
            render(row, false, true);
            try {
                const pending = audio.play();
                if (pending && pending.catch) pending.catch(function () {
                    stop(state, 'This example could not be played. Please try again.');
                });
            } catch (error) {
                stop(state, 'This example could not be played. Please try again.');
            }
        }
        document.querySelectorAll('[data-explore-example]').forEach(function (row) {
            row.querySelector('[data-example-toggle]').addEventListener('click', function () {
                if (active && active.row === row) stop(active);
                else start(row);
            });
        });
        document.querySelectorAll('#explore-catalogue details').forEach(function (section) {
            section.addEventListener('toggle', function () {
                if (!section.open && active && section.contains(active.row)) stop(active);
            });
        });
        return {stop: function () { stop(active); }};
    }
    if (typeof module !== 'undefined' && module.exports) module.exports = install;
    if (root.document) {
        const player = install(root.document, function () { return new root.Audio(); }, root);
        root.addEventListener('pagehide', player.stop);
    }
})(typeof window !== 'undefined' ? window : globalThis);
