(function () {
    'use strict';
    const form = document.querySelector('[data-access-limits-form]');
    const tones = {available: 'green', limited: 'warning', blocked: 'red'};
    const originals = new Map();
    document.querySelectorAll('[data-status-select]').forEach(select => {
        originals.set(select, select.value);
        select.addEventListener('change', function () {
            Object.values(tones).forEach(tone => select.classList.remove('admin-table__badge--' + tone));
            select.classList.add('admin-table__badge--' + tones[select.value]);
            const note = select.closest('td').querySelector('[data-access-note]');
            if (note) {
                note.textContent = (JSON.parse(select.dataset.statusNotes || '{}')[select.value] || '');
            }
            if (!form) return;
            const dirty = Array.from(originals).some(([field, value]) => field.value !== value);
            form.querySelector('[data-save-status]').textContent = dirty ? 'Unsaved changes' : 'All changes saved';
        });
    });
    if (!form) return;
    let submitting = false;
    form.addEventListener('submit', function () {
        submitting = true;
        form.querySelector('[data-save-limits]').disabled = true;
        form.querySelector('[data-save-status]').textContent = 'Saving…';
    });
    window.addEventListener('pageshow', function () {
        submitting = false;
        form.querySelector('[data-save-limits]').disabled = false;
    });
    window.addEventListener('beforeunload', function (event) {
        if (!submitting && Array.from(originals).some(([field, value]) => field.value !== value)) {
            event.preventDefault(); event.returnValue = '';
        }
    });
})();
