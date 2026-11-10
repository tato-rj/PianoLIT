(function (doc, win) {
    'use strict';
    var form = doc.querySelector('[data-quiz-editor]');
    if (!form) return;
    var rows = form.querySelector('[data-quiz-questions]');
    var template = form.querySelector('[data-quiz-question-template]');
    var add = form.querySelector('[data-add-question]');
    var status = form.querySelector('[data-save-status]');
    var nextIndex = Array.from(rows.querySelectorAll('[data-question-index]')).reduce(function (next, row) {
        return Math.max(next, Number(row.dataset.questionIndex) + 1);
    }, 0);
    function changed() { if (status) status.textContent = 'Unsaved changes'; }
    function numberQuestions() {
        rows.querySelectorAll('[data-quiz-question]').forEach(function (row, index) {
            var label = 'Question ' + (index + 1);
            row.querySelector('[data-question-number]').textContent = label;
            row.querySelector('[data-question-legend]').textContent = label;
            var remove = row.querySelector('[data-remove-question]');
            remove.setAttribute('aria-label', 'Remove question ' + (index + 1));
            remove.title = 'Remove question ' + (index + 1);
        });
    }
    form.addEventListener('input', changed);
    form.addEventListener('change', changed);
    add.addEventListener('click', function () {
        rows.insertAdjacentHTML('beforeend', template.innerHTML.replace(/__INDEX__/g, String(nextIndex++)));
        numberQuestions();
        rows.lastElementChild.querySelector('input').focus();
        changed();
    });
    rows.addEventListener('click', function (event) {
        var remove = event.target.closest('[data-remove-question]');
        if (!remove) return;
        remove.closest('[data-quiz-question]').remove();
        numberQuestions();
        changed();
        add.focus();
    });
    var imageInput = form.querySelector('#image-input');
    if (win.SimpleCropper && imageInput) {
        new win.SimpleCropper({
            imageInput: '#image-input', uploadButton: '#upload-button',
            confirmButton: '#confirm-button', cancelButton: '#cancel-button',
            submitButton: '#quiz-editor [data-save-quiz]'
        }).create();
        imageInput.addEventListener('change', function () {
            if (imageInput.files && imageInput.files.length) imageInput.name = 'cover_image';
        });
        form.querySelector('#cancel-button').addEventListener('click', function () {
            imageInput.value = '';
            imageInput.name = '';
        });
    }
    numberQuestions();
}(document, window));
