(function () {
    'use strict';

    function install(doc, view) {
        doc.querySelectorAll('[data-table-bulk-delete]').forEach(function (toolbar) {
            var table = doc.getElementById(toolbar.dataset.tableId);
            var form = doc.getElementById(toolbar.dataset.formId);
            if (!table || !form || toolbar.dataset.bulkReady) return;
            toolbar.dataset.bulkReady = 'true';
            var all = toolbar.querySelector('[data-bulk-select-all]');
            var count = toolbar.querySelector('[data-bulk-count]');
            var open = toolbar.querySelector('[data-bulk-open]');
            var submit = form.querySelector('[data-bulk-submit]');
            var confirmation = doc.querySelector('[data-bulk-confirm-count="' + form.id + '"]');
            function boxes() {
                return Array.prototype.slice.call(table.querySelectorAll('input[name="ids[]"]')).filter(function (box) {
                    return !box.disabled && (box.form === form || (form.hasAttribute('data-bulk-preview') && box.getAttribute('form') === form.id));
                });
            }
            function update() {
                var rows = boxes();
                var selected = rows.filter(function (box) { return box.checked; }).length;
                all.checked = rows.length > 0 && selected === rows.length;
                all.indeterminate = selected > 0 && selected < rows.length;
                all.disabled = rows.length === 0;
                open.disabled = submit.disabled = selected === 0;
                count.textContent = selected + ' selected';
                if (confirmation) confirmation.textContent = selected;
            }
            all.addEventListener('change', function () {
                boxes().forEach(function (box) { box.checked = all.checked; });
                update();
            });
            table.addEventListener('change', update);
            form.addEventListener('submit', function (event) {
                if (submit.disabled || form.hasAttribute('data-bulk-preview')) event.preventDefault();
            });
            view.addEventListener('pageshow', update);
            update();
        });
    }

    if (typeof module !== 'undefined' && module.exports) module.exports = install;
    if (typeof document !== 'undefined') {
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function () { install(document, window); }, {once: true});
        else install(document, window);
    }
}());
