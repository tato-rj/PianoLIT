(function () {
    const createModal = document.querySelector('#add-modal.autoshow');
    if (createModal) window.bootstrap.Modal.getOrCreateInstance(createModal).show();
    const form = document.querySelector('[data-playlist-order]');
    if (!form) return;
    const body = form.querySelector('tbody');
    const status = form.querySelector('[data-save-status]');
    const save = form.querySelector('[data-playlist-save]');
    if (!save) return;
    const initial = Array.from(body.children).map(row => row.dataset.playlistId).join(',');
    function update() {
        const rows = Array.from(body.children);
        rows.forEach((row, index) => {
            row.querySelector('[data-playlist-position]').textContent = index + 1;
            row.querySelector('[data-playlist-move="up"]').disabled = index === 0;
            row.querySelector('[data-playlist-move="down"]').disabled = index === rows.length - 1;
        });
        status.textContent = rows.map(row => row.dataset.playlistId).join(',') === initial ? 'All changes saved' : 'Unsaved changes';
    }
    body.addEventListener('click', function (event) {
        const button = event.target.closest('[data-playlist-move]');
        if (!button || button.disabled) return;
        const row = button.closest('[data-playlist-id]');
        const movingUp = button.dataset.playlistMove === 'up';
        const sibling = movingUp ? row.previousElementSibling : row.nextElementSibling;
        if (!sibling) return;
        body.insertBefore(movingUp ? row : sibling, movingUp ? sibling : row);
        update();
        // Keep keyboard focus on the moved row, including at either boundary.
        const next = button.disabled ? row.querySelector('[data-playlist-move]:not(:disabled)') : button;
        if (next) next.focus();
    });
    if (window.jQuery && window.jQuery.fn.sortable) {
        window.jQuery(body).sortable({
            handle: '.sort-handle', items: '[data-playlist-id]', axis: 'y',
            helper: function (_, row) { row.children().each(function () { window.jQuery(this).width(window.jQuery(this).width()); }); return row; },
            stop: update,
        });
    }
    form.addEventListener('submit', function (event) {
        if (save.disabled) { event.preventDefault(); return; }
        save.disabled = true;
        status.textContent = 'Saving changes…';
    });
    window.addEventListener('pageshow', function () { save.disabled = false; update(); });
    update();
})();
