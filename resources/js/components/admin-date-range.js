(function () {
    'use strict';

    function day(value) {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) return null;
        var date = new Date(value + 'T00:00:00Z');
        return isFinite(date.getTime()) && date.toISOString().slice(0, 10) === value ? date.getTime() : null;
    }

    function validate(from, to, required, maxDays) {
        if (required && (!from || !to)) return 'Choose both dates for a custom range.';
        var first = from ? day(from) : null, last = to ? day(to) : null;
        if ((from && first === null) || (to && last === null)) return 'Enter valid dates.';
        if (first !== null && last !== null) {
            if (last < first) return 'The To date must be on or after the From date.';
            if (maxDays && (last - first) / 86400000 + 1 > maxDays) return 'Choose a period of at most ' + maxDays + ' days.';
        }
        return '';
    }

    function install(doc) {
        var states = [];
        Array.prototype.forEach.call(doc.querySelectorAll('[data-admin-date-range]'), function (root) {
            if (root.dataset.dateRangeReady) return;
            root.dataset.dateRangeReady = 'true';
            var form = root.closest('form'), summary = root.querySelector('summary'), panel = root.querySelector('.admin-date-range-panel');
            var from = root.querySelector('[data-date-range-from]'), to = root.querySelector('[data-date-range-to]');
            var draftFrom = root.querySelector('[data-date-draft-from]'), draftTo = root.querySelector('[data-date-draft-to]');
            var error = root.querySelector('[data-date-range-error]'), text = root.querySelector('[data-date-range-summary]');
            var presets = Array.prototype.slice.call(root.querySelectorAll('[data-date-preset]'));
            var period = form && root.dataset.periodName ? form.elements.namedItem(root.dataset.periodName) : null;
            var committed = root.dataset.currentPreset || '', draft = committed;

            function preset(key) { return presets.find(function (button) { return button.dataset.datePreset === key; }); }
            function highlight() { presets.forEach(function (button) { button.setAttribute('aria-pressed', button.dataset.datePreset === draft ? 'true' : 'false'); }); }
            function clearError() { error.hidden = true; error.textContent = ''; draftFrom.removeAttribute('aria-invalid'); draftTo.removeAttribute('aria-invalid'); }
            function restore() {
                var selected = preset(period ? period.value : committed);
                if (!selected || !selected.dataset.from) selected = preset(root.dataset.defaultPreset);
                draft = 'custom';
                draftFrom.value = from.value || (selected && selected.dataset.from) || '';
                draftTo.value = to.value || (selected && selected.dataset.to) || '';
                highlight(); clearError();
            }
            function update() {
                var selected = preset(committed);
                var label = !period && selected ? selected.textContent.trim() : root.dataset.placeholder;
                text.textContent = label;
                summary.setAttribute('aria-label', root.dataset.label + ': ' + label);
                root.dataset.currentPreset = committed;
            }
            function fit() {
                if (!root.open) return;
                var view = doc.defaultView, rect = root.getBoundingClientRect();
                if (rect.bottom < 16 || rect.top > view.innerHeight - 16) { close(false); return; }
                panel.style.maxHeight = Math.max(1, view.innerHeight - 32) + 'px';
                var width = panel.getBoundingClientRect().width;
                panel.style.left = Math.max(16, Math.min(rect.left, view.innerWidth - width - 16)) - rect.left + 'px';
                panel.style.top = 'calc(100% + 8px)';
                var height = panel.getBoundingClientRect().height;
                var below = view.innerHeight - rect.bottom - 24, above = rect.top - 24;
                var upwards = height > below && above > below;
                panel.style.maxHeight = Math.max(1, upwards ? above : below) + 'px';
                panel.style.top = upwards ? -panel.getBoundingClientRect().height - 8 + 'px' : 'calc(100% + 8px)';
            }
            function close(focus) {
                root.open = false; summary.setAttribute('aria-expanded', 'false'); restore();
                if (focus) summary.focus();
            }
            function open(focus) {
                states.forEach(function (state) { if (state.root !== root && state.root.open) state.close(false); });
                restore(); root.open = true; summary.setAttribute('aria-expanded', 'true'); fit();
                if (focus) draftFrom.focus();
            }
            function commit(focus) {
                var message = validate(draftFrom.value, draftTo.value, root.dataset.required === 'true', Number(root.dataset.maxDays));
                if (message) {
                    error.textContent = message; error.hidden = false;
                    draftFrom.setAttribute('aria-invalid', 'true'); draftTo.setAttribute('aria-invalid', 'true');
                    draftFrom.focus(); fit(); return false;
                }
                from.value = draftFrom.value; to.value = draftTo.value;
                committed = !from.value && !to.value && !period ? '' : (draft || 'custom');
                if (period) period.value = committed;
                update(); close(focus); return true;
            }
            summary.addEventListener('click', function () { if (!root.open) restore(); });
            root.addEventListener('toggle', function (event) {
                if (event.target !== root) return;
                summary.setAttribute('aria-expanded', root.open ? 'true' : 'false');
                if (root.open) { states.forEach(function (state) { if (state.root !== root && state.root.open) state.close(false); }); fit(); }
            });
            presets.forEach(function (button) {
                button.addEventListener('click', function () {
                    draft = button.dataset.datePreset;
                    if (draft !== 'custom') { draftFrom.value = button.dataset.from; draftTo.value = button.dataset.to; }
                    highlight(); clearError();
                    if (draft === 'custom') draftFrom.focus();
                });
            });
            [draftFrom, draftTo].forEach(function (field) {
                field.addEventListener('input', function () { draft = 'custom'; highlight(); clearError(); });
                field.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter') { event.preventDefault(); commit(true); }
                });
            });
            root.querySelector('[data-date-range-apply]').addEventListener('click', function () { commit(true); });
            root.querySelector('[data-date-range-clear]').addEventListener('click', function () {
                from.value = ''; to.value = ''; committed = root.dataset.defaultPreset || '';
                if (period) period.value = committed;
                update(); close(true);
            });
            if (period) period.addEventListener('change', function () {
                committed = period.value;
                if (committed === 'custom') { open(true); return; }
                from.value = ''; to.value = ''; update(); close(false);
            });
            if (form) form.addEventListener('submit', function (event) {
                if (root.open && !commit(false)) { event.preventDefault(); return; }
                if (period && period.value === 'custom') {
                    var message = validate(from.value, to.value, true, Number(root.dataset.maxDays));
                    if (message) {
                        event.preventDefault(); open(true); error.textContent = message; error.hidden = false;
                        draftFrom.setAttribute('aria-invalid', 'true'); draftTo.setAttribute('aria-invalid', 'true');
                    }
                }
            });
            states.push({root: root, close: close, fit: fit});
            update(); restore();
        });
        doc.addEventListener('pointerdown', function (event) {
            states.forEach(function (state) { if (state.root.open && !state.root.contains(event.target)) state.close(false); });
        });
        doc.addEventListener('focusin', function (event) {
            states.forEach(function (state) { if (state.root.open && !state.root.contains(event.target)) state.close(false); });
        });
        doc.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') states.forEach(function (state) { if (state.root.open) { event.preventDefault(); state.close(true); } });
        });
        doc.defaultView.addEventListener('resize', function () { states.forEach(function (state) { state.fit(); }); });
        doc.defaultView.addEventListener('scroll', function (event) {
            states.forEach(function (state) {
                if (!event.target || !event.target.nodeType || !state.root.contains(event.target)) state.fit();
            });
        }, true);
    }

    if (typeof module !== 'undefined') module.exports = {validate: validate, install: install};
    if (typeof document !== 'undefined') {
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function () { install(document); });
        else install(document);
    }
})();
