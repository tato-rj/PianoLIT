(function () {
    'use strict';
    document.querySelectorAll('[data-file-dropzone]').forEach(function (root) {
        var input = root.querySelector('input[type="file"]');
        var area = root.querySelector('.file-dropzone');
        var selection = root.querySelector('[data-file-selection]');
        if (!input || input.dataset.dropzoneBound) return;
        input.dataset.dropzoneBound = 'true';
        var depth = 0;
        function showSelection() {
            var files = Array.from(input.files || []);
            input.setCustomValidity('');
            var maxFiles = Number(input.dataset.maxFiles);
            var maxBytes = Number(input.dataset.maxBytes);
            if (files.length > maxFiles) input.setCustomValidity('Choose up to ' + maxFiles + (maxFiles === 1 ? ' image.' : ' images.'));
            else if (files.some(function (file) { return file.size > maxBytes; })) input.setCustomValidity('Choose images up to ' + Math.floor(maxBytes / 1048576) + ' MB each.');
            selection.textContent = files.length ? files.map(function (file) { return file.name; }).join(', ') : 'No files selected';
            if (input.validationMessage && files.length) input.reportValidity();
        }
        input.addEventListener('change', showSelection);
        root.addEventListener('dragenter', function (event) {
            if (input.disabled || !Array.from(event.dataTransfer.types || []).includes('Files')) return;
            event.preventDefault();
            depth++;
            area.classList.add('is-dragging');
        });
        root.addEventListener('dragover', function (event) {
            if (input.disabled || !Array.from(event.dataTransfer.types || []).includes('Files')) return;
            event.preventDefault();
            event.dataTransfer.dropEffect = 'copy';
        });
        root.addEventListener('dragleave', function () {
            depth = Math.max(0, depth - 1);
            if (!depth) area.classList.remove('is-dragging');
        });
        root.addEventListener('drop', function (event) {
            depth = 0;
            area.classList.remove('is-dragging');
            if (input.disabled || !event.dataTransfer.files.length) return;
            event.preventDefault();
            try {
                input.files = event.dataTransfer.files;
                input.dispatchEvent(new Event('change', {bubbles: true}));
            } catch (failure) {
                input.setCustomValidity('Click the upload area to select your images.');
                input.reportValidity();
            }
        });
        if (input.form) input.form.addEventListener('reset', function () {
            input.setCustomValidity('');
            selection.textContent = 'No files selected';
            depth = 0;
            area.classList.remove('is-dragging');
        });
        showSelection();
    });
}());
