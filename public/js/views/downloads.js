(function () {
    'use strict';

    var library = document.querySelector('[data-download-library]');
    if (!library) return;

    var controls = Array.from(library.querySelectorAll('[data-download-view]'));
    var storageKey = 'pianolit.downloads.view';
    try {
        var saved = window.localStorage.getItem(storageKey);
        controls.forEach(function (control) {
            if (control.value === saved) control.checked = true;
        });
    } catch (error) { /* Native controls also work without storage or JavaScript. */ }

    controls.forEach(function (control) {
        control.addEventListener('change', function () {
            if (!control.checked) return;
            try { window.localStorage.setItem(storageKey, control.value); } catch (error) { /* Storage may be unavailable. */ }
        });
    });

    library.querySelectorAll('[data-download-cover]').forEach(function (image) {
        function fallback() {
            image.hidden = true;
            var placeholder = image.parentElement.querySelector('[data-download-placeholder]');
            if (placeholder) placeholder.hidden = false;
        }
        image.addEventListener('error', fallback);
        if (image.complete && !image.naturalWidth) fallback();
    });
}());
