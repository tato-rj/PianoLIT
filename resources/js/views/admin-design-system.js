(function() {
    'use strict';

    var gallery = document.querySelector('[data-design-system]');
    if (!gallery) return;
    var editors = new WeakMap();
    var editorCount = 0;
    var resetAll = gallery.querySelector('[data-ds-reset-all]');
    var states = [];

    function previewFor(sample) {
        var preview = sample.querySelector('[data-ds-preview]');
        if (!preview) return null;
        if (preview.hasAttribute('data-ds-icon')) return preview.querySelector('svg');
        return preview.querySelector('.admin-table') || preview;
    }

    function fitField(field) {
        field.style.height = 'auto';
        field.style.height = field.scrollHeight + 2 + 'px';
    }

    function updateActions(state) {
        var changed = state.rows.some(function(row) {
            return row.edited;
        });
        var invalid = state.rows.some(function(row) {
            return row.invalid;
        });
        state.reset.disabled = !changed;
        state.status.textContent = invalid
            ? 'Check the highlighted value.'
            : changed
            ? 'Preview changed'
            : 'Edit a value to preview';
        state.sample.classList.toggle('design-system__sample--edited', changed);
        if (resetAll)
            resetAll.disabled = !states.some(function(editor) {
                return editor.rows.some(function(row) {
                    return row.edited;
                });
            });
    }

    // Rebuild only this specimen's overrides. Starting from its original style
    // also restores shorthand declarations and their original !important flags.
    function applyPreview(state) {
        if (state.originalStyle === null) state.preview.removeAttribute('style');
        else state.preview.setAttribute('style', state.originalStyle);
        state.rows.forEach(function(row) {
            if (row.applied)
                state.preview.style.setProperty(row.property, row.applied, 'important');
        });
    }

    function editValue(state, row) {
        var value = row.field.value.trim();
        row.edited = true;
        row.invalid = !!value && !window.CSS.supports(row.property, value);
        row.field.setAttribute('aria-invalid', String(row.invalid));
        row.error.hidden = !row.invalid;
        row.field.classList.toggle('design-system__value--edited', !!value);
        if (!row.invalid) {
            row.applied = value;
            applyPreview(state);
            measureSample(state.sample);
        }
        fitField(row.field);
        updateActions(state);
    }

    function resetPreview(state) {
        state.rows.forEach(function(row) {
            row.edited = false;
            row.invalid = false;
            row.applied = '';
            row.field.setAttribute('aria-invalid', 'false');
            row.field.classList.remove('design-system__value--edited');
            row.error.hidden = true;
        });
        applyPreview(state);
        measureSample(state.sample);
        updateActions(state);
        state.status.textContent = 'Original styles restored';
    }

    function createEditor(sample) {
        var preview = previewFor(sample);
        var output = sample.querySelector('[data-ds-specs]');
        if (!preview || !output) return null;
        var title = sample.querySelector('.design-system__sample-label');
        var name = sample.getAttribute('data-ds-label') || (title ? title.textContent : 'Sample');
        var state = {
            sample: sample,
            preview: preview,
            originalStyle: preview.getAttribute('style'),
            rows: []
        };
        var id = 'ds-editor-' + ++editorCount;
        var properties = sample.getAttribute('data-ds-properties').split(',');
        var fragment = document.createDocumentFragment();
        properties.forEach(function(property, index) {
            var term = document.createElement('dt');
            var label = document.createElement('label');
            var value = document.createElement('dd');
            var field = document.createElement('textarea');
            var error = document.createElement('span');
            var row = {
                property: property,
                field: field,
                error: error,
                edited: false,
                invalid: false,
                applied: ''
            };
            field.id = id + '-' + index;
            field.className = 'design-system__value';
            field.rows = 1;
            field.spellcheck = false;
            field.setAttribute('data-ds-property', property);
            field.setAttribute('data-text-improver', 'off');
            field.setAttribute('autocomplete', 'off');
            field.setAttribute('autocapitalize', 'off');
            field.setAttribute('aria-label', name + ': ' + property);
            field.setAttribute('aria-describedby', field.id + '-error');
            label.htmlFor = field.id;
            label.textContent = property;
            error.id = field.id + '-error';
            error.className = 'design-system__value-error';
            error.textContent = 'Invalid CSS value';
            error.hidden = true;
            field.addEventListener('input', function() {
                editValue(state, row);
            });
            // Escape discards an unfinished draft and returns to the last valid value.
            field.addEventListener('keydown', function(event) {
                if (event.key !== 'Escape') return;
                event.preventDefault();
                row.invalid = false;
                row.field.setAttribute('aria-invalid', 'false');
                row.error.hidden = true;
                row.field.value = row.applied || row.measured;
                fitField(row.field);
                updateActions(state);
            });
            term.appendChild(label);
            value.appendChild(field);
            value.appendChild(error);
            fragment.appendChild(term);
            fragment.appendChild(value);
            state.rows.push(row);
        });
        output.appendChild(fragment);

        var actions = document.createElement('div');
        var status = document.createElement('span');
        var reset = document.createElement('button');
        actions.className = 'design-system__editor-actions';
        status.className = 'design-system__editor-status';
        status.setAttribute('role', 'status');
        reset.className = 'design-system__reset';
        reset.type = 'button';
        reset.textContent = 'Reset';
        reset.disabled = true;
        reset.setAttribute('aria-label', 'Reset ' + name + ' styles');
        reset.addEventListener('click', function() {
            resetPreview(state);
        });
        actions.appendChild(status);
        actions.appendChild(reset);
        sample.appendChild(actions);
        state.reset = reset;
        state.status = status;
        editors.set(sample, state);
        states.push(state);
        updateActions(state);
        return state;
    }

    function measureSample(sample) {
        var state = editors.get(sample) || createEditor(sample);
        if (!state) return;
        var style = window.getComputedStyle(state.preview);
        state.rows.forEach(function(row) {
            var measured = style.getPropertyValue(row.property).trim();
            if (state.preview.hasAttribute('data-ds-color')) measured = hexColor(measured);
            row.measured = measured;
            // Keep nodes, drafts and caret stable while hover/focus/resize update
            // the untouched readings. An empty field removes just that override.
            if (
                !row.edited &&
                document.activeElement !== row.field &&
                row.field.value !== measured
            ) {
                row.field.value = measured;
                row.field.placeholder = measured ? '' : 'Mixed / unset';
            }
            fitField(row.field);
        });
    }

    function hexColor(value) {
        var match = value.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
        if (!match) return value;
        return (
            '#' +
            match
                .slice(1)
                .map(function(part) {
                    return ('0' + Number(part).toString(16)).slice(-2);
                })
                .join('')
                .toUpperCase()
        );
    }

    function refresh() {
        gallery.querySelectorAll('[data-ds-sample]').forEach(measureSample);
        var rootStyle = window.getComputedStyle(document.documentElement);
        gallery.querySelectorAll('[data-ds-token]').forEach(function(element) {
            element.textContent = rootStyle
                .getPropertyValue(element.getAttribute('data-ds-token'))
                .trim();
        });
    }

    var frame;
    var pendingSamples = new Set();
    var refreshAll = false;
    function scheduleRefresh(event) {
        var sample =
            event && event.target instanceof Element
                ? event.target.closest('[data-ds-sample]')
                : null;
        if (sample) pendingSamples.add(sample);
        else if (!event || event.type === 'resize') refreshAll = true;
        else return;
        window.cancelAnimationFrame(frame);
        frame = window.requestAnimationFrame(function() {
            if (refreshAll) refresh();
            else pendingSamples.forEach(measureSample);
            pendingSamples.clear();
            refreshAll = false;
        });
    }

    // Read states after the browser applies hover, focus, active and choice changes.
    [
        'pointerover',
        'pointerout',
        'pointerdown',
        'pointerup',
        'focusin',
        'focusout',
        'change',
        'keydown',
        'keyup'
    ].forEach(function(event) {
        gallery.addEventListener(event, scheduleRefresh);
    });
    gallery.addEventListener('transitionend', scheduleRefresh);
    if (resetAll)
        resetAll.addEventListener('click', function() {
            states
                .filter(function(state) {
                    return state.rows.some(function(row) {
                        return row.edited;
                    });
                })
                .forEach(resetPreview);
        });
    window.addEventListener('resize', scheduleRefresh);
    if (document.fonts && document.fonts.ready)
        document.fonts.ready.then(function() {
            scheduleRefresh();
        });
    refresh();
})();
