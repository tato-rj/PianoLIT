(function () {
    'use strict';
    if (!window.app || !window.app.routes || !window.app.routes.improveText) return;

    var states = new WeakMap();
    var sequence = 0;
    var sizes = window.ResizeObserver ? new window.ResizeObserver(function (entries) {
        entries.forEach(function (entry) {
            var state = states.get(entry.target);
            if (state) fit(state.field, state);
        });
    }) : null;

    function fit(field, state) {
        if (!field.getBoundingClientRect) return;
        var target = state.editor ? state.editor.getContainer() : field;
        if (!target) return;
        var width = target.getBoundingClientRect().width;
        state.bar.hidden = width === 0;
        state.bar.style.width = width + 'px';
    }

    function editorFor(field) {
        return window.tinymce && field.id ? window.tinymce.get(field.id) : null;
    }

    function attach(field) {
        if (states.has(field)) {
            sync(field, states.get(field));
            return;
        }
        // Cloned form rows can include a previously attached toolbar.
        var cloned = field.nextElementSibling;
        if (cloned && cloned.classList.contains('admin-text-improver')) cloned.remove();
        var bar = document.createElement('div');
        bar.className = 'admin-text-improver';
        var id = 'admin-text-options-' + (++sequence);
        bar.innerHTML = '<button type="button" class="admin-text-improve-toggle" aria-expanded="false" aria-controls="' + id + '">Improve text</button>' +
            '<div class="admin-text-improve-options" id="' + id + '" hidden>' +
            '<label><select class="form-select-sm" aria-label="Rewrite length"><option value="shorter">Shorter</option><option value="same" selected>Same length</option><option value="longer">Longer</option></select></label>' +
            '<label><select class="form-select-sm" aria-label="Rewrite tone"><option value="casual">Casual</option><option value="same" selected>Same tone</option><option value="formal">Formal</option></select></label>' +
            '<button type="button" class="btn btn-sm btn-primary admin-text-improve-run">Rewrite text</button></div>' +
            '<div class="admin-text-improve-status" role="status" aria-live="polite" hidden></div>';
        field.insertAdjacentElement('afterend', bar);
        field.classList.add('admin-improvable-textarea');
        var state = {field: field, bar: bar, toggle: bar.querySelector('button'), options: bar.querySelector('.admin-text-improve-options'),
            run: bar.querySelector('.admin-text-improve-run'), status: bar.querySelector('[role="status"]'),
            selects: bar.querySelectorAll('select'), busy: false, revision: 0, editor: null};
        states.set(field, state);
        if (sizes) sizes.observe(field);
        field.addEventListener('input', function () { state.revision++; });
        field.addEventListener('change', function () { state.revision++; });
        if (field.form) field.form.addEventListener('reset', function () { state.revision++; });
        state.toggle.addEventListener('click', function () {
            if (unavailable(field, state)) return;
            state.options.hidden = !state.options.hidden;
            state.bar.classList.toggle('bg-light', !state.options.hidden);
            state.toggle.setAttribute('aria-expanded', String(!state.options.hidden));
            if (!state.options.hidden) {
                Array.prototype.forEach.call(state.selects, function (select) { select.value = 'same'; });
                state.selects[0].focus();
            }
        });
        state.run.addEventListener('click', function () { rewrite(field, state); });
        sync(field, state);
    }

    function unavailable(field, state) {
        return state.busy || field.matches(':disabled') || field.readOnly ||
            !!(state.editor && state.editor.mode && state.editor.mode.isReadOnly());
    }

    function sync(field, state) {
        var editor = editorFor(field);
        if (!editor && state.editor) {
            state.editor = null;
            state.revision++;
        }
        if (editor && editor.initialized && editor !== state.editor) {
            state.editor = editor;
            editor.on('input change Undo Redo SetContent', function () { state.revision++; });
            if (sizes) {
                var container = editor.getContainer();
                states.set(container, state);
                sizes.observe(container);
            }
        }
        if (state.editor && state.editor.getContainer()) {
            var container = state.editor.getContainer();
            container.classList.add('admin-improvable-editor');
            if (container.nextElementSibling !== state.bar) container.insertAdjacentElement('afterend', state.bar);
        }
        var disabled = unavailable(field, state);
        [state.toggle, state.run].concat(Array.prototype.slice.call(state.selects)).forEach(function (control) {
            if (control.disabled !== disabled) control.disabled = disabled;
        });
        fit(field, state);
    }

    function status(state, message, error) {
        state.status.textContent = message;
        state.status.hidden = !message;
        state.status.classList.toggle('text-danger', !!error);
    }

    function snapshot(field, state) {
        var editor = state.editor;
        var source = editor ? editor.getContent() : field.value;
        if (!editor) return {source: source, texts: [source], apply: function (texts) { field.value = texts[0]; }};
        // Rewrite text nodes only. Model output never becomes executable HTML,
        // and the original document's links, images and formatting stay intact.
        var documentCopy = document.createElement('div');
        documentCopy.innerHTML = source;
        var walker = document.createTreeWalker(documentCopy, NodeFilter.SHOW_TEXT, null, false);
        var nodes = [], node;
        while ((node = walker.nextNode())) {
            if (node.nodeValue.trim() && !node.parentElement.closest('script,style')) nodes.push(node);
        }
        return {source: source, texts: nodes.map(function (item) { return item.nodeValue; }), apply: function (texts) {
            nodes.forEach(function (item, index) {
                var original = item.nodeValue;
                item.nodeValue = original.match(/^\s*/)[0] + texts[index].trim() + original.match(/\s*$/)[0];
            });
            editor.undoManager.transact(function () { editor.setContent(documentCopy.innerHTML); });
            editor.save();
            editor.setDirty(true);
            editor.fire('change');
        }};
    }

    function rewrite(field, state) {
        sync(field, state);
        if (unavailable(field, state)) return;
        var draft = snapshot(field, state);
        if (!draft.texts.length || !draft.texts.join('').trim()) {
            status(state, 'Add some text first.', true);
            if (state.editor) state.editor.focus(); else field.focus();
            return;
        }
        var revision = state.revision;
        var form = field.form;
        var limit = field.maxLength > 0 ? Math.min(field.maxLength, 50000) : null;
        state.busy = true;
        field.setAttribute('aria-busy', 'true');
        state.run.textContent = 'Rewriting…';
        status(state, 'Improving the wording…');
        sync(field, state);
        $.ajax({url: window.app.routes.improveText, method: 'POST', dataType: 'json', timeout: 55000,
            contentType: 'application/json', headers: {'X-CSRF-TOKEN': window.app.csrfToken},
            data: JSON.stringify({texts: draft.texts, length: state.selects[0].value, tone: state.selects[1].value, max_length: limit})
        }).done(function (data) {
            if (!field.isConnected || field.form !== form || (state.editor && editorFor(field) !== state.editor) ||
                state.revision !== revision || (state.editor ? state.editor.getContent() : field.value) !== draft.source ||
                field.matches(':disabled') || field.readOnly ||
                (state.editor && state.editor.mode && state.editor.mode.isReadOnly())) {
                status(state, 'The field changed while rewriting. Your latest text was kept.', true);
                return;
            }
            if (!data || !Array.isArray(data.texts) || data.texts.length !== draft.texts.length ||
                data.texts.some(function (text) { return typeof text !== 'string' || !text.trim(); })) {
                status(state, 'No complete rewrite was returned. Please try again.', true);
                return;
            }
            if (limit && data.texts.join('').length > limit) {
                status(state, 'The rewrite exceeds this field’s character limit. Try a shorter length.', true);
                return;
            }
            draft.apply(data.texts);
            field.dispatchEvent(new Event('input', {bubbles: true}));
            field.dispatchEvent(new Event('change', {bubbles: true}));
            status(state, '');
            state.options.hidden = true;
            state.bar.classList.toggle('bg-light', false);
            state.toggle.setAttribute('aria-expanded', 'false');
            if (state.editor) state.editor.focus(); else field.focus();
        }).fail(function (xhr) {
            var message = xhr.status === 419 || xhr.status === 401 ? 'Your session expired. Reload the page and sign in again.' :
                xhr.status === 429 ? 'Too many rewrites. Please wait a minute and try again.' : 'Text could not be improved. Please try again.';
            if (xhr.responseJSON) {
                var errors = xhr.responseJSON.errors;
                var first = errors && errors[Object.keys(errors)[0]];
                message = first && first[0] || xhr.responseJSON.message || message;
            }
            status(state, message, true);
        }).always(function () {
            state.busy = false;
            field.removeAttribute('aria-busy');
            state.run.textContent = 'Rewrite text';
            sync(field, state);
        });
    }

    function scan() { Array.prototype.forEach.call(document.querySelectorAll('textarea'), attach); }
    function start() {
        scan();
        new MutationObserver(scan).observe(document.body, {childList: true, subtree: true,
            attributes: true, attributeFilter: ['disabled', 'readonly']});
        document.addEventListener('submit', function (event) {
            Array.prototype.forEach.call(document.querySelectorAll('textarea'), function (field) {
                var state = states.get(field);
                if (field.form === event.target && state && state.busy) {
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    status(state, 'Wait for the rewrite to finish before saving.', true);
                }
            });
        }, true);
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
    else start();
})();
