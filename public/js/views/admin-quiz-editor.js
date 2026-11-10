(function (doc, win) {
    'use strict';
    var form = doc.querySelector('[data-quiz-editor]');
    if (!form) return;
    var rows = form.querySelector('[data-quiz-questions]');
    var template = form.querySelector('[data-quiz-question-template]');
    var add = form.querySelector('[data-add-question]');
    var status = form.querySelector('[data-save-status]');
    var mediaPicker = createMediaPicker();
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
        if (mediaPicker && mediaPicker.handleClick(event)) return;
        var remove = event.target.closest('[data-remove-question]');
        if (!remove) return;
        var row = remove.closest('[data-quiz-question]');
        stopAudio(row);
        row.remove();
        numberQuestions();
        changed();
        add.focus();
    });

    function stopAudio(root) {
        if (root.querySelectorAll) root.querySelectorAll('audio').forEach(function (audio) { audio.pause(); });
    }

    function createMediaPicker() {
        var picker = doc.getElementById && doc.getElementById('quiz-media-picker');
        if (!picker) return null;
        var content = picker.querySelector('[data-quiz-media-content]');
        var title = picker.querySelector('.modal-title');
        var loading = doc.getElementById('quiz-media-loading');
        var failure = doc.getElementById('quiz-media-error');
        var targetRow = null, trigger = null, request = 0, controller = null, lastUrl = '';

        function invalidate() {
            request++;
            if (controller) controller.abort();
            stopAudio(content);
        }

        async function load(url) {
            invalidate();
            var current = request;
            lastUrl = url;
            controller = win.AbortController ? new win.AbortController() : null;
            content.innerHTML = loading.innerHTML;
            content.setAttribute('aria-busy', 'true');
            try {
                var response = await win.fetch(url, {headers: {'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest'}, signal: controller ? controller.signal : undefined});
                if (!response.ok || response.redirected || response.headers.get('X-Quiz-Media-Library') !== '1') throw new Error('media-library');
                var html = await response.text();
                if (current !== request || !targetRow || !form.contains(targetRow)) return;
                content.innerHTML = html;
            } catch (error) {
                if (current === request && targetRow && form.contains(targetRow) && error.name !== 'AbortError') content.innerHTML = failure.innerHTML;
            } finally {
                if (current === request) content.setAttribute('aria-busy', 'false');
            }
        }

        function setSelection(row, selection) {
            var block = row.querySelector('[data-question-media]');
            var image = block.querySelector('[data-question-media-image]');
            var audio = block.querySelector('[data-question-media-audio]');
            audio.pause();
            audio.removeAttribute('src');
            audio.load();
            image.removeAttribute('src');
            image.hidden = true;
            audio.hidden = true;
            block.querySelector('[data-question-media-value]').value = selection ? selection.path : '';
            block.querySelector('[data-question-media-preview]').hidden = !selection;
            block.querySelector('[data-remove-question-media]').hidden = !selection;
            block.querySelector('[data-question-media-missing]').hidden = true;
            block.querySelector('[data-question-media-name]').textContent = selection ? selection.name : '';
            if (selection) {
                if (selection.type === 'audio') {
                    audio.src = selection.url;
                    audio.hidden = false;
                } else {
                    image.src = selection.url;
                    image.alt = selection.name;
                    image.hidden = false;
                }
            }
            changed();
        }

        picker.addEventListener('hide.bs.modal', function () {
            invalidate();
            targetRow = null;
        });
        picker.addEventListener('hidden.bs.modal', function () {
            if (trigger && form.contains(trigger)) trigger.focus();
            trigger = null;
        });
        picker.addEventListener('play', function (event) {
            content.querySelectorAll('audio').forEach(function (audio) { if (audio !== event.target) audio.pause(); });
        }, true);
        picker.addEventListener('submit', function (event) {
            event.preventDefault();
            var url = new win.URL(event.target.action, win.location.href);
            url.search = new win.URLSearchParams(new win.FormData(event.target)).toString();
            load(url.href);
        });
        picker.addEventListener('click', function (event) {
            var selected = event.target.closest('[data-select-quiz-media]');
            if (selected && targetRow && form.contains(targetRow)) {
                setSelection(targetRow, selected.dataset);
                win.bootstrap.Modal.getOrCreateInstance(picker).hide();
                return;
            }
            var link = event.target.closest('a');
            if (link && content.contains(link)) {
                event.preventDefault();
                load(link.href);
            } else if (event.target.closest('[data-retry-quiz-media]')) load(lastUrl);
        });
        return {handleClick: function (event) {
            var choose = event.target.closest('[data-choose-question-media]');
            if (choose) {
                targetRow = choose.closest('[data-quiz-question]');
                stopAudio(targetRow);
                trigger = choose;
                title.textContent = choose.dataset.chooseQuestionMedia === 'audio' ? 'Choose audio' : 'Choose image';
                win.bootstrap.Modal.getOrCreateInstance(picker).show();
                load(choose.dataset.url);
                return true;
            }
            var remove = event.target.closest('[data-remove-question-media]');
            if (remove) {
                setSelection(remove.closest('[data-quiz-question]'), null);
                return true;
            }
            return false;
        }};
    }
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
