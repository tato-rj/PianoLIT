(function () {
    'use strict';

    function install(doc, bootstrap) {
        Array.prototype.forEach.call(doc.querySelectorAll('[data-checkbox-menu]'), function (root) {
            if (root.dataset.checkboxMenuReady) return;
            root.dataset.checkboxMenuReady = 'true';
            var form = root.closest('form'), trigger = root.querySelector('[data-bs-toggle="dropdown"]');
            var text = root.querySelector('[data-checkbox-menu-summary]'), error = root.querySelector('[data-checkbox-menu-error]');
            var options = Array.prototype.slice.call(root.querySelectorAll('[data-checkbox-menu-option]'));

            function selected() { return options.filter(function (option) { return option.checked; }); }
            function update() {
                var checked = selected();
                text.textContent = checked.length === options.length ? root.dataset.allLabel : (checked.map(function (option) { return option.dataset.label; }).join(' + ') || 'Choose options');
                if (checked.length) { error.hidden = true; options.forEach(function (option) { option.removeAttribute('aria-invalid'); }); }
            }
            options.forEach(function (option) { option.addEventListener('change', update); });
            root.querySelector('[data-checkbox-menu-all]').addEventListener('click', function () {
                options.forEach(function (option) { option.checked = true; });
                update();
            });
            if (form) form.addEventListener('submit', function (event) {
                if (selected().length) return;
                event.preventDefault();
                error.hidden = false;
                options.forEach(function (option) { option.setAttribute('aria-invalid', 'true'); });
                if (bootstrap && bootstrap.Dropdown) bootstrap.Dropdown.getOrCreateInstance(trigger).show();
                options[0].focus();
            });
        });
    }

    if (typeof module !== 'undefined') module.exports = {install: install};
    if (typeof document !== 'undefined') {
        var start = function () { install(document, window.bootstrap); };
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
        else start();
    }
})();
