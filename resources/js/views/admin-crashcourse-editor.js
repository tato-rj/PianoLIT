(function (doc, win) {
    'use strict';

    const reorderUrl = doc.currentScript.dataset.courseReorderUrl;
    const form = doc.getElementById('course-editor');
    const save = form.querySelector('#submit-button');
    const status = form.querySelector('[data-save-status]');
    const imageInput = form.querySelector('#image-input');
    const confirm = form.querySelector('#confirm-button');
    const coverError = form.querySelector('[data-course-cover-error]');
    let dirty = form.dataset.hasOldInput === 'true';
    let submitting = false;

    function markChanged(event) {
        if (event?.target === imageInput) return;
        dirty = true;
        status.textContent = 'Unsaved changes';
    }

    form.addEventListener('input', markChanged);
    form.addEventListener('change', markChanged);
    form.addEventListener('submit', function (event) {
        if (save.disabled) {
            event.preventDefault();
            confirm.focus();
            return;
        }

        submitting = true;
    });
    win.addEventListener('beforeunload', function (event) {
        if (!dirty || submitting) return;
        event.preventDefault();
        event.returnValue = '';
    });
    win.addEventListener('pageshow', function () {
        submitting = false;
    });

    const cropper = new win.SimpleCropper({
        imageInput: '#image-input',
        uploadButton: '#upload-button',
        confirmButton: '#confirm-button',
        cancelButton: '#cancel-button',
        submitButton: '#course-editor #submit-button',
    });
    cropper.create();

    imageInput.addEventListener('change', function (event) {
        const file = imageInput.files[0];
        imageInput.name = '';
        coverError.hidden = true;
        form.querySelectorAll('.cropper-dimensions').forEach(function (field) { field.value = ''; });
        if (file && /\.jpe?g$/i.test(file.name) && file.size > 0 && file.size < 1048576) return;

        event.stopImmediatePropagation();
        imageInput.value = '';
        cropper.$image.attr('src', cropper.defaultImage).css('visibility', 'visible').show().next('canvas').remove();
        coverError.textContent = 'Choose a JPG image under 1 MB.';
        coverError.hidden = false;
    }, true);
    confirm.addEventListener('click', function (event) {
        if (!form.elements.cropped_width.value || !form.elements.cropped_height.value) {
            event.stopImmediatePropagation();
            return;
        }
        imageInput.name = 'cover_image';
        markChanged();
    }, true);
    form.querySelector('#cancel-button').addEventListener('click', function () {
        imageInput.value = '';
        imageInput.name = '';
    });

    const body = doc.querySelector('#course-lessons tbody');
    if (!reorderUrl || !body || !win.jQuery?.fn.sortable) return;
    const rows = function () { return Array.from(body.querySelectorAll('[data-lesson-id]')); };
    if (rows().length < 2) return;

    function showOrderAlert(result) {
        doc.querySelectorAll('.alert-container.alert-top').forEach(function (alert) { alert.remove(); });
        const template = doc.getElementById('course-order-' + result);
        doc.body.appendChild(template.content.cloneNode(true));
    }

    let savedRows = rows();
    const sortable = win.jQuery(body).sortable({
        items: '> [data-lesson-id]',
        handle: '.sort-handle',
        axis: 'y',
        helper: function (_, row) {
            const cells = row.children();
            const helper = row.clone();
            helper.children().each(function (index) {
                win.jQuery(this).width(cells.eq(index).width());
            });
            return helper;
        },
        update: function () {
            const order = rows();
            sortable.sortable('disable');
            body.setAttribute('aria-busy', 'true');

            win.jQuery.ajax({
                url: reorderUrl,
                type: 'PATCH',
                data: {ids: order.map(function (row) { return row.dataset.lessonId; })},
            }).done(function () {
                savedRows = order;
                order.forEach(function (row, position) {
                    row.querySelector('[data-lesson-position]').textContent = position + 1;
                });
                showOrderAlert('success');
            }).fail(function () {
                body.append(...savedRows);
                showOrderAlert('error');
            }).always(function () {
                body.removeAttribute('aria-busy');
                sortable.sortable('enable');
            });
        },
    });
})(document, window);
