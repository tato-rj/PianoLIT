(function (doc, win) {
    'use strict';
    var form = doc.querySelector('[data-piece-editor]');
    if (!form) return;
    var fields = form.querySelector('fieldset');
    if (fields.disabled) return;

    var status = form.querySelector('[data-editor-save-status]');
    function markChanged() {
        if (status) status.textContent = 'Unsaved changes';
    }
    form.addEventListener('input', markChanged);
    form.addEventListener('change', markChanged);

    if (win.SimpleCropper) {
        new win.SimpleCropper({
            ratio: 25 / 13,
            imageInput: '#image-input',
            uploadButton: '#upload-button',
            confirmButton: '#confirm-button',
            cancelButton: '#cancel-button',
            submitButton: '#edit-form [data-save-piece]'
        }).create();
        var imageInput = form.querySelector('#image-input');
        imageInput.addEventListener('change', function () {
            this.name = 'cover_image';
        });
        form.querySelector('#cancel-button').addEventListener('click', function () {
            imageInput.value = '';
            imageInput.name = '';
        });
    }

    var publicToggle = form.elements.namedItem('is_public');
    if (publicToggle) {
        function updateScoreFields() {
            form.querySelector('[data-public-fields]').hidden = !publicToggle.checked;
            form.querySelector('[data-external-score]').hidden = publicToggle.checked;
        }
        publicToggle.addEventListener('change', updateScoreFields);
        updateScoreFields();
    }

    var rows = form.querySelector('[data-video-rows]');
    var template = form.querySelector('[data-video-template]');
    if (rows && template) {
        var nextIndex = Array.from(rows.querySelectorAll('[data-video-index]')).reduce(function (next, row) {
            return Math.max(next, Number(row.dataset.videoIndex) + 1);
        }, 0);
        form.querySelector('[data-add-video]').addEventListener('click', function () {
            rows.insertAdjacentHTML('beforeend', template.innerHTML.replace(/__INDEX__/g, String(nextIndex++)));
            var row = rows.lastElementChild;
            row.querySelector('select').focus();
            markChanged();
        });
        rows.addEventListener('click', function (event) {
            var remove = event.target.closest('[data-remove-video]');
            if (!remove) return;
            remove.closest('[data-video-row]').remove();
            markChanged();
        });
        var defaults = {
            performance: ['Performance', 'Watch a video recording of this piece'],
            slow: ['Slow performance', 'Learn with a slow video recording'],
            tutorial: ['Tutorial', ''],
            synthesia: ['Synthesia', 'Follow along one note at a time'],
            harmony: ['Harmonic analysis', 'A full harmonic analysis one measure at a time']
        };
        rows.addEventListener('change', function (event) {
            if (event.target.tagName !== 'SELECT') return;
            var values = defaults[event.target.value];
            if (!values) return;
            var row = event.target.closest('[data-video-row]');
            row.querySelector('[data-video-type]').value = values[0];
            row.querySelector('input[name$="[description]"]').value = values[1];
        });
    }

    var sample = form.querySelector('[data-sample-description]');
    var description = form.elements.namedItem('description');
    var error = form.querySelector('[data-description-error]');
    sample.addEventListener('click', function () {
        var wasReadOnly = description.readOnly;
        sample.disabled = true;
        description.readOnly = true;
        error.hidden = true;
        win.axios.get(form.dataset.descriptionUrl, {params: {piece_id: form.dataset.pieceId}})
            .then(function (response) {
                description.value = response.data;
                markChanged();
            })
            .catch(function () {
                error.textContent = 'The sample could not be loaded. Try again.';
                error.hidden = false;
            })
            .then(function () {
                sample.disabled = false;
                description.readOnly = wasReadOnly;
            });
    });
}(document, window));
