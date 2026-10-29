(function (doc, win) {
    'use strict';
    const installations = new Map();

    function install(root) {
        if (root.dataset.linkShareReady) return;
        root.dataset.linkShareReady = 'true';
        const modal = root.querySelector('.modal') || root.closest('.modal');
        const input = root.querySelector('input');
        const copy = root.querySelector('[data-share-copy]');
        const status = root.querySelector('[data-share-status]');
        const error = root.querySelector('[data-share-error]');
        let copying = false;
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

        function hiding() { generation++; reset(); }
        function hidden() {
            if (tooltip) { tooltip.dispose(); tooltip = null; }
        }
        modal.addEventListener('hide.bs.modal', hiding);
        modal.addEventListener('hidden.bs.modal', hidden);
        installations.set(root, function () {
            hiding(); hidden();
            modal.removeEventListener('hide.bs.modal', hiding);
            modal.removeEventListener('hidden.bs.modal', hidden);
        });
    }

    function init() {
        installations.forEach(function (dispose, root) {
            if (root.isConnected === false) { dispose(); installations.delete(root); }
        });
        doc.querySelectorAll('[data-link-share]').forEach(install);
    }
    win.PianoLITLinkShare = {init: init};
    if (doc.readyState === 'loading') doc.addEventListener('DOMContentLoaded', init);
    else init();
}(document, window));
