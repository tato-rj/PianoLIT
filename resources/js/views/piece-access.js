(function (root) {
    'use strict';

    function installPreviewGuards(document, showUpgrade) {
        const stopped = new WeakSet();

        function enforce(event) {
            const media = event.target;
            if (!media || !/^(AUDIO|VIDEO)$/.test(media.tagName)) return;

            const preview = media.getAttribute('data-media-preview');
            if (preview === null || preview === '') return;
            const limit = Number(preview);
            if (!Number.isFinite(limit) || limit < 0) return;
            // A zero-second gate opens only on playback/seeking, never page-load events.
            if (limit === 0 && !stopped.has(media) && media.currentTime === 0 && event.type !== 'play' && event.type !== 'playing') return;
            if (!stopped.has(media) && media.currentTime < limit && event.type !== 'ended') return;

            const firstStop = !stopped.has(media);
            stopped.add(media);
            media.pause();
            if (media.currentTime > limit) media.currentTime = limit;

            if (firstStop || event.type === 'play') {
                if (firstStop && root.PianoActivity) root.PianoActivity.record('premium_restriction', {reason: 'media_preview_limit'});
                // The upgrade dialog must be visible outside native/Plyr fullscreen.
                if (media.webkitDisplayingFullscreen && media.webkitExitFullscreen) media.webkitExitFullscreen();
                if (document.fullscreenElement && document.exitFullscreen) {
                    const exit = document.exitFullscreen();
                    if (exit && exit.catch) exit.catch(function () {});
                }
                showUpgrade(media.tagName === 'AUDIO' ? 'audio' : 'video', media);
            }
        }

        // Capture non-bubbling media events, including players inserted via AJAX.
        ['play', 'playing', 'timeupdate', 'seeking', 'seeked', 'ended'].forEach(function (event) {
            document.addEventListener(event, enforce, true);
        });
    }

    function renderScorePreview(container) {
        const image = container.querySelector('.score-preview-pages img');
        const status = container.querySelector('.score-preview-status');
        function ready() {
            status.textContent = 'Preview of the first score page.';
            container.querySelector('[data-page-label]').textContent = 'Page 1';
            container.querySelector('[data-zoom-label]').textContent = root.matchMedia('(max-width: 767px)').matches ? '100%' : '75%';
        }
        function failed() {
            status.textContent = 'The score preview could not be loaded. Please try again later.';
        }
        if (image.complete) {
            if (image.naturalWidth) ready(); else failed();
        } else {
            image.addEventListener('load', ready, {once: true});
            image.addEventListener('error', failed, {once: true});
        }
    }

    root.PieceAccess = {installPreviewGuards: installPreviewGuards, renderScorePreview: renderScorePreview};
})(typeof window !== 'undefined' ? window : globalThis);
