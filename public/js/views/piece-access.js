(function (root) {
    'use strict';

    function installPreviewGuards(document, showUpgrade) {
        const stopped = new WeakSet();

        function enforce(event) {
            const media = event.target;
            if (!media || !/^(AUDIO|VIDEO)$/.test(media.tagName)) return;

            const limit = Number(media.getAttribute('data-media-preview'));
            if (!Number.isFinite(limit) || limit <= 0) return;
            if (!stopped.has(media) && media.currentTime < limit && event.type !== 'ended') return;

            const firstStop = !stopped.has(media);
            stopped.add(media);
            media.pause();
            if (media.currentTime > limit) media.currentTime = limit;

            if (firstStop || event.type === 'play') {
                // The upgrade dialog must be visible outside native/Plyr fullscreen.
                if (media.webkitDisplayingFullscreen && media.webkitExitFullscreen) media.webkitExitFullscreen();
                if (document.fullscreenElement && document.exitFullscreen) {
                    const exit = document.exitFullscreen();
                    if (exit && exit.catch) exit.catch(function () {});
                }
                showUpgrade();
            }
        }

        // Capture non-bubbling media events, including players inserted via AJAX.
        ['play', 'playing', 'timeupdate', 'seeking', 'seeked', 'ended'].forEach(function (event) {
            document.addEventListener(event, enforce, true);
        });
    }

    async function renderScorePreview(container, pdfjs) {
        const status = container.querySelector('.score-preview-status');
        const pages = container.querySelector('.score-preview-pages');
        try {
            const pdf = await pdfjs.getDocument({url: container.getAttribute('data-pdf-url')}).promise;
            // Restricted viewers only see a blurred preview of the first page.
            const page = await pdf.getPage(1);
            const viewport = page.getViewport({scale: 1});
            const canvas = container.ownerDocument.createElement('canvas');
            canvas.className = 'w-100 d-block mb-3 border';
            canvas.width = viewport.width;
            canvas.height = viewport.height;
            pages.appendChild(canvas);
            await page.render({canvasContext: canvas.getContext('2d'), viewport: viewport}).promise;
            page.cleanup();
            status.hidden = true;
        } catch (error) {
            status.textContent = 'The score preview could not be loaded. Please try again later.';
        }
    }

    root.PieceAccess = {installPreviewGuards: installPreviewGuards, renderScorePreview: renderScorePreview};
})(typeof window !== 'undefined' ? window : globalThis);
