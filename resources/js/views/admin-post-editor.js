(function (doc, win) {
    'use strict';
    var form = doc.querySelector('[data-post-editor]');
    if (!form) return;
    var status = form.querySelector('[data-save-status]');
    var save = form.querySelector('#submit-button');
    var dirty = false;
    var submitting = false;
    function changed() {
        dirty = true;
        if (status) status.textContent = 'Unsaved changes';
    }
    form.addEventListener('input', changed);
    form.addEventListener('change', changed);
    form.addEventListener('submit', function (event) {
        if (save.disabled) {
            event.preventDefault();
            form.querySelector('#confirm-button').focus();
            return;
        }
        submitting = true;
    });
    win.addEventListener('beforeunload', function (event) {
        if (!dirty || submitting) return;
        event.preventDefault();
        event.returnValue = '';
    });
    win.addEventListener('pageshow', function () { submitting = false; });

    if (win.tinymce) {
        function watchEditor(editor) {
            if (editor.id === 'editor') editor.on('input change undo redo', changed);
        }
        var editor = win.tinymce.get('editor');
        if (editor) watchEditor(editor);
        win.tinymce.on('AddEditor', function (event) { watchEditor(event.editor); });
    }

    var imageInput = form.querySelector('#image-input');
    var initialImageName = imageInput.name;
    if (win.SimpleCropper) {
        new win.SimpleCropper({
            imageInput: '#image-input', uploadButton: '#upload-button',
            confirmButton: '#confirm-button', cancelButton: '#cancel-button',
            submitButton: '#post-editor #submit-button'
        }).create();
        imageInput.addEventListener('change', function () { imageInput.name = 'cover_image'; });
        form.querySelector('#cancel-button').addEventListener('click', function () {
            imageInput.value = '';
            imageInput.name = initialImageName;
        });
    }

    var rows = form.querySelector('[data-reference-rows]');
    var template = form.querySelector('[data-reference-template]');
    var add = form.querySelector('[data-add-reference]');
    var next = Array.from(rows.querySelectorAll('[data-reference-row]')).reduce(function (index, row) {
        var rowIndex = Number(row.dataset.referenceIndex);
        return Number.isSafeInteger(rowIndex) && rowIndex >= 0 ? Math.max(index, rowIndex + 1) : index;
    }, 0);
    add.addEventListener('click', function () {
        rows.insertAdjacentHTML('beforeend', template.innerHTML.replace(/__INDEX__/g, String(next++)));
        rows.lastElementChild.querySelector('input').focus();
        changed();
    });
    rows.addEventListener('click', function (event) {
        var button = event.target.closest('[data-remove-reference]');
        if (!button) return;
        var row = button.closest('[data-reference-row]');
        var neighbour = row.nextElementSibling || row.previousElementSibling;
        row.remove();
        if (neighbour) neighbour.querySelector('input').focus();
        else add.focus();
        changed();
    });
})(document, window);
