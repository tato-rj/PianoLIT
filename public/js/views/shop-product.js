(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory;
    else factory(root);
}(typeof window !== 'undefined' ? window : this, function (root) {
    'use strict';

    const doc = root.document;
    const preview = doc.querySelector('[data-book-preview]');
    if (preview) initPreview(preview);
    const audioButton = doc.querySelector('[data-product-audio]');
    if (audioButton) initAudio(audioButton);

    const reviewModal = doc.getElementById('review-modal');
    const signIn = doc.querySelector('[data-review-signin]');
    if (reviewModal && signIn) {
        signIn.addEventListener('click', function () {
            reviewModal.addEventListener('hidden.bs.modal', function () {
                root.bootstrap.Modal.getOrCreateInstance(doc.getElementById('modal-auth')).show();
            }, {once: true});
            root.bootstrap.Modal.getOrCreateInstance(reviewModal).hide();
        });
    }
    // Let the browser retain the draft, rating and CSRF token on dismissal.
    // Named validation errors belong to this modal, never the account modal.
    if (reviewModal && reviewModal.querySelector('.is-invalid, [aria-invalid="true"]')) {
        root.bootstrap.Modal.getOrCreateInstance(reviewModal).show();
    }

    function initPreview(element) {
        const modal = element.closest('.modal');
        const stage = element.querySelector('[data-book-stage]');
        const host = element.querySelector('[data-book-host]');
        const book = element.querySelector('[data-book]');
        const images = Array.from(book.querySelectorAll('img'));
        const pages = Array.from(book.children);
        const loading = element.querySelector('[data-book-loading]');
        const error = element.querySelector('[data-book-error]');
        const previous = element.querySelector('[data-book-previous]');
        const next = element.querySelector('[data-book-next]');
        const counter = element.querySelector('[data-book-counter]');
        const magnify = element.querySelector('[data-book-magnify]');
        const zoom = element.querySelector('[data-book-zoom]');
        const zoomImage = element.querySelector('[data-book-zoom-image]');
        const thumbnails = Array.from(element.querySelectorAll('[data-book-page]'));
        const reducedMotion = root.matchMedia('(prefers-reduced-motion: reduce)');
        let $book = null;
        let open = false;
        let ready = false;
        let initializing = false;
        let zoomed = false;
        let current = 1;
        let ratio = 0.707;
        let resizeTimer;

        images.forEach(function (img) {
            img.addEventListener('error', function () {
                img.hidden = true;
                img.nextElementSibling.hidden = false;
                error.hidden = false;
            });
            img.addEventListener('load', function () {
                img.hidden = false;
                img.nextElementSibling.hidden = true;
                error.hidden = !images.some(image => image.hidden);
            });
        });

        function firstImageReady() {
            return new Promise(function (resolve) {
                const first = images[0];
                let timer;
                function finish() {
                    root.clearTimeout(timer);
                    first.removeEventListener('load', finish);
                    first.removeEventListener('error', finish);
                    resolve();
                }
                first.addEventListener('load', finish);
                first.addEventListener('error', finish);
                timer = root.setTimeout(finish, 10000);
                images.forEach(img => { if (!img.getAttribute('src')) img.src = img.dataset.previewSrc; });
                if (first.complete) finish();
            });
        }

        function dimensions() {
            const spread = stage.clientWidth >= 640 && images.length > 1;
            const pageWidth = Math.max(1, Math.floor(Math.min(
                (stage.clientWidth - 32) / (spread ? 2 : 1),
                (stage.clientHeight - 32) * ratio
            )));
            return {width: pageWidth * (spread ? 2 : 1), height: Math.round(pageWidth / ratio), display: spread ? 'double' : 'single'};
        }

        async function initialize() {
            if (ready || initializing) return;
            initializing = true;
            await firstImageReady();
            initializing = false;
            if (!open) return;
            if (images[0].naturalWidth && images[0].naturalHeight) ratio = images[0].naturalWidth / images[0].naturalHeight;
            loading.hidden = true;
            host.hidden = false;
            try {
                if (!root.jQuery || !root.jQuery.fn.turn) throw new Error('Page turning unavailable');
                $book = root.jQuery(book);
                const size = dimensions();
                host.style.width = size.width + 'px';
                host.style.height = size.height + 'px';
                $book.turn(Object.assign(size, {
                    page: current,
                    autoCenter: true,
                    gradients: true,
                    elevation: 80,
                    duration: reducedMotion.matches ? 0 : 700,
                    when: {turned: function (event, page) { current = page; update(); }}
                }));
            } catch (e) {
                if ($book) {
                    try { $book.turn('destroy'); } catch (ignored) { /* Retain readable sample pages. */ }
                }
                $book = null;
                book.classList.add('product-preview__book--fallback');
                element.querySelector('[data-book-hint]').textContent = 'Use the arrows or page numbers to browse the preview';
                resize();
            }
            ready = true;
            stage.setAttribute('aria-busy', 'false');
            magnify.disabled = false;
            update();
        }

        function view() {
            return $book && !zoomed ? $book.turn('view').filter(page => page > 0 && page <= images.length) : [current];
        }

        function update() {
            if (!ready) return;
            const visible = view();
            previous.disabled = visible.includes(1);
            next.disabled = visible.includes(images.length);
            counter.textContent = (visible.length > 1 ? 'Pages ' + visible.join('–') : 'Page ' + current) + ' of ' + images.length;
            thumbnails.forEach(function (button) {
                const active = visible.includes(Number(button.dataset.bookPage));
                button.classList.toggle('active', active);
                if (Number(button.dataset.bookPage) === current) button.setAttribute('aria-current', 'page');
                else button.removeAttribute('aria-current');
            });
            pages.forEach((page, index) => page.setAttribute('aria-hidden', String(zoomed || !visible.includes(index + 1))));
            if (!$book) pages.forEach((page, index) => { page.hidden = index !== current - 1; });
            if (zoomed) {
                zoomImage.src = images[current - 1].dataset.previewSrc;
                zoomImage.alt = images[current - 1].alt;
                zoom.scrollTop = 0;
                zoom.scrollLeft = 0;
            }
        }

        function goTo(page) {
            if (!ready || !open) return;
            current = Math.max(1, Math.min(images.length, page));
            if ($book && !zoomed) $book.turn('page', current);
            else update();
        }

        function turn(direction) {
            if (!ready || (direction < 0 ? previous.disabled : next.disabled)) return;
            if ($book && !zoomed) $book.turn(direction < 0 ? 'previous' : 'next');
            else goTo(current + direction);
        }

        function resize() {
            if (!open || zoomed) return;
            const size = dimensions();
            host.style.width = ($book ? size.width : size.width / (size.display === 'double' ? 2 : 1)) + 'px';
            host.style.height = size.height + 'px';
            if ($book) {
                $book.turn('stop');
                $book.turn('display', size.display);
                $book.turn('size', size.width, size.height);
            } else {
                book.style.width = size.width / (size.display === 'double' ? 2 : 1) + 'px';
                book.style.height = size.height + 'px';
            }
            update();
        }

        previous.addEventListener('click', () => turn(-1));
        next.addEventListener('click', () => turn(1));
        thumbnails.forEach(button => button.addEventListener('click', () => goTo(Number(button.dataset.bookPage))));
        modal.addEventListener('keydown', function (event) {
            if (!ready || event.altKey || event.ctrlKey || event.metaKey || /INPUT|TEXTAREA|SELECT/.test(event.target.tagName)) return;
            if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
                event.preventDefault();
                turn(event.key === 'ArrowLeft' ? -1 : 1);
            }
        });
        magnify.addEventListener('click', function () {
            zoomed = !zoomed;
            magnify.setAttribute('aria-pressed', String(zoomed));
            element.querySelector('[data-book-zoom-label]').textContent = zoomed ? 'Fit pages' : 'Zoom in';
            host.hidden = zoomed;
            zoom.hidden = !zoomed;
            if (!zoomed) {
                resize();
                if ($book) $book.turn('page', current);
            }
            update();
        });
        element.querySelector('[data-book-retry]').addEventListener('click', function () {
            images.filter(img => img.hidden || !img.naturalWidth).forEach(img => { img.src = img.dataset.previewSrc; });
        });
        modal.addEventListener('shown.bs.modal', function () {
            open = true;
            if (ready) resize();
            else initialize();
        });
        modal.addEventListener('hide.bs.modal', function () {
            open = false;
            if ($book) $book.turn('stop');
        });
        root.addEventListener('resize', function () {
            root.clearTimeout(resizeTimer);
            resizeTimer = root.setTimeout(resize, 100);
        });
    }

    function initAudio(button) {
        const audio = new root.Audio();
        const error = doc.querySelector('[data-audio-error]');
        let generation = 0;
        let active = false;
        let playbackTimer;
        function render(state) {
            button.querySelector('[data-audio-play]').hidden = state !== 'idle';
            button.querySelector('[data-audio-stop]').hidden = state !== 'playing';
            button.querySelector('[data-audio-loading]').hidden = state !== 'loading';
            button.disabled = state === 'loading';
            button.setAttribute('aria-pressed', String(state === 'playing'));
        }
        function stop() {
            ++generation;
            active = false;
            root.clearTimeout(playbackTimer);
            audio.pause();
            try { audio.currentTime = 0; } catch (ignored) { /* Unloaded media may not be seekable. */ }
            render('idle');
        }
        button.addEventListener('click', async function () {
            if (active) { stop(); return; }
            const request = ++generation;
            active = true;
            error.hidden = true;
            render('loading');
            if (!audio.src) audio.src = button.dataset.src;
            playbackTimer = root.setTimeout(function () {
                if (request !== generation) return;
                stop();
                error.hidden = false;
            }, 12000);
            try {
                await audio.play();
                if (request === generation) {
                    root.clearTimeout(playbackTimer);
                    render('playing');
                }
            } catch (e) {
                if (request !== generation) return;
                stop();
                error.hidden = false;
            }
        });
        audio.addEventListener('ended', stop);
        audio.addEventListener('error', function () { stop(); error.hidden = false; });
        doc.addEventListener('visibilitychange', function () { if (doc.hidden) stop(); });
        root.addEventListener('pagehide', stop);
    }
}));
