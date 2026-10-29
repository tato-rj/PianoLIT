(function (doc, win) {
    'use strict';

    function install(root) {
        if (root.dataset.linkShareReady) return;
        root.dataset.linkShareReady = 'true';
        const modal = root.querySelector('.modal');
        const input = root.querySelector('input');
        const copy = root.querySelector('[data-share-copy]');
        const native = root.querySelector('[data-share-native]');
        const status = root.querySelector('[data-share-status]');
        const error = root.querySelector('[data-share-error]');
        const data = {title: root.dataset.shareTitle, text: root.dataset.shareTitle, url: root.dataset.shareUrl};
        let copying = false;
        let sharing = false;
        let generation = 0;
        let tooltip;
        let feedbackTimer;

        function reset() {
            win.clearTimeout(feedbackTimer);
            if (tooltip) tooltip.hide();
            status.textContent = '';
            error.hidden = true;
        }
        function fail(message) {
            reset();
            root.querySelector('[data-share-error-message]').textContent = message;
            error.hidden = false;
        }
        function confirmCopy() {
            status.textContent = 'Copied!';
            if (!tooltip && win.bootstrap && win.bootstrap.Tooltip) {
                tooltip = new win.bootstrap.Tooltip(copy, {
                    title: 'Copied!', trigger: 'manual', placement: 'bottom',
                    fallbackPlacements: [], container: modal, animation: false
                });
            }
            if (tooltip) tooltip.show();
            feedbackTimer = win.setTimeout(reset, 1600);
        }
        function select() {
            input.focus();
            input.select();
            input.setSelectionRange(0, input.value.length);
        }
        function fallbackCopy() {
            select();
            return typeof doc.execCommand === 'function' && doc.execCommand('copy');
        }

        // iOS/iPadOS Messages uses an ampersand for its optional body field.
        const ios = /iPad|iPhone|iPod/.test(win.navigator.userAgent || '')
            || (win.navigator.platform === 'MacIntel' && win.navigator.maxTouchPoints > 1);
        const sms = root.querySelector('[data-share-sms]');
        if (ios && sms) sms.setAttribute('href', 'sms:&body=' + encodeURIComponent(root.dataset.shareMessage));

        try {
            native.hidden = typeof win.navigator.share !== 'function'
                || (typeof win.navigator.canShare === 'function' && !win.navigator.canShare(data));
        } catch (_) { native.hidden = true; }

        async function copyLink() {
            if (copying) return;
            copying = true;
            const started = generation;
            reset();
            try {
                let copied = false;
                if (win.navigator.clipboard && typeof win.navigator.clipboard.writeText === 'function') {
                    try {
                        await win.navigator.clipboard.writeText(root.dataset.shareUrl);
                        copied = true;
                    } catch (_) { /* The selected field also works when clipboard permission is denied. */ }
                }
                if (started !== generation) return;
                if (!copied) copied = fallbackCopy();
                if (!copied) throw new Error('Copy unavailable');
                confirmCopy();
            } catch (_) {
                if (started !== generation) return;
                select();
                fail('Select the page link and copy it.');
            } finally { copying = false; }
        }
        copy.addEventListener('click', copyLink);
        input.addEventListener('click', copyLink);
        input.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                return copyLink();
            }
        });

        native.addEventListener('click', async function () {
            if (sharing || native.hidden) return;
            sharing = true;
            const started = generation;
            reset();
            try {
                await win.navigator.share(data);
            } catch (problem) {
                if (started === generation && (!problem || problem.name !== 'AbortError')) fail('Sharing could not open. Copy the page link instead.');
            } finally { sharing = false; }
        });
        modal.addEventListener('hide.bs.modal', function () { generation++; reset(); });
        modal.addEventListener('hidden.bs.modal', function () {
            if (tooltip) { tooltip.dispose(); tooltip = null; }
        });
    }

    function init() { doc.querySelectorAll('[data-link-share]').forEach(install); }
    win.PianoLITLinkShare = {init: init};
    if (doc.readyState === 'loading') doc.addEventListener('DOMContentLoaded', init);
    else init();
}(document, window));
