(function (doc, win) {
    'use strict';
    const root = doc.querySelector('[data-playlist-editor-root]');
    if (!root) return;
    const form = root.querySelector('[data-playlist-editor]');
    const body = form.querySelector('#playlist-selected-table tbody');
    const empty = root.querySelector('[data-playlist-empty]');
    const count = form.querySelector('[data-playlist-count]');
    const status = form.querySelector('[data-save-status]');
    const save = form.querySelector('[data-playlist-save]');
    const finder = root.querySelector('[data-playlist-finder]');
    const results = finder.querySelector('[data-finder-results]');
    const finderStatus = finder.querySelector('[data-finder-status]');
    const error = finder.querySelector('[data-finder-error]');
    let dirty = form.dataset.restored === 'true', submitting = false;
    let requestNumber = 0, controller, coverUrl;

    function selectedRows() { return Array.from(body.querySelectorAll('[data-selected-piece]')); }
    function markChanged() { dirty = true; status.textContent = 'Unsaved changes'; }
    function syncFinder() {
        const ids = new Set(selectedRows().map(row => row.dataset.selectedPiece));
        results.querySelectorAll('[data-piece-add]').forEach(button => {
            const added = ids.has(button.dataset.pieceAdd);
            if (!button.dataset.addLabel) button.dataset.addLabel = button.getAttribute('aria-label');
            const icon = button.querySelector('i');
            if (icon) icon.classList.replace(added ? 'icon-plus' : 'icon-check', added ? 'icon-check' : 'icon-plus');
            button.replaceChildren(...(icon ? [icon] : []), doc.createTextNode(added ? 'Added' : 'Add'));
            button.disabled = added;
            button.setAttribute('aria-label', added ? button.dataset.addLabel.replace(/^Add /, 'Already in playlist: ').replace(/ to playlist$/, '') : button.dataset.addLabel);
            button.title = added ? 'Already in playlist' : 'Add piece';
        });
    }
    function update(changed) {
        const rows = selectedRows();
        if (!rows.length && !body.children.length) body.appendChild(empty.content.cloneNode(true));
        rows.forEach((row, index) => {
            row.querySelector('[data-piece-position]').textContent = index + 1;
            row.querySelector('[data-piece-move="up"]').disabled = index === 0;
            row.querySelector('[data-piece-move="down"]').disabled = index === rows.length - 1;
        });
        count.textContent = rows.length + (rows.length === 1 ? ' piece' : ' pieces') + ' in this playlist';
        syncFinder();
        if (changed) markChanged();
    }
    form.addEventListener('input', markChanged);
    form.addEventListener('change', markChanged);
    body.addEventListener('click', function (event) {
        const button = event.target.closest('[data-piece-remove], [data-piece-move]');
        if (!button || button.disabled) return;
        const row = button.closest('[data-selected-piece]');
        if (button.hasAttribute('data-piece-remove')) {
            const next = row.nextElementSibling || row.previousElementSibling;
            row.remove();
            update(true);
            finderStatus.textContent = row.dataset.pieceName + ' removed. Save changes to apply your selection.';
            if (next) next.querySelector('[data-piece-remove]').focus();
            else form.querySelector('[data-bs-target="#playlist-piece-finder"]').focus();
        } else {
            const up = button.dataset.pieceMove === 'up';
            const sibling = up ? row.previousElementSibling : row.nextElementSibling;
            if (!sibling) return;
            body.insertBefore(up ? row : sibling, up ? sibling : row);
            update(true);
            const focus = button.disabled ? row.querySelector('[data-piece-move]:not(:disabled)') : button;
            if (focus) focus.focus();
        }
    });
    finder.addEventListener('click', function (event) {
        const button = event.target.closest('[data-piece-add]');
        if (button) {
            if (button.disabled || selectedRows().some(row => row.dataset.selectedPiece === button.dataset.pieceAdd)) return;
            const template = button.closest('[data-finder-piece]').querySelector('[data-piece-template]');
            const row = template.content.firstElementChild.cloneNode(true);
            Array.from(body.children).filter(item => !item.hasAttribute('data-selected-piece')).forEach(item => item.remove());
            body.appendChild(row);
            update(true);
            finderStatus.textContent = row.dataset.pieceName + ' added. Save changes when you are finished.';
            return;
        }
        const link = event.target.closest('a[href]');
        if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button > 0 || link.target === '_blank') return;
        const url = new URL(link.href, win.location.href), endpoint = new URL(finder.dataset.url, win.location.href);
        if (url.origin !== endpoint.origin || url.pathname !== endpoint.pathname) return;
        event.preventDefault();
        load(url.href);
    });
    finder.addEventListener('submit', function (event) {
        const filters = event.target.closest('[data-admin-filter-form]');
        if (!filters) return;
        event.preventDefault();
        const url = new URL(filters.action, win.location.href);
        url.search = new URLSearchParams(new FormData(filters)).toString();
        load(url.href);
    });

    async function load(url) {
        const current = ++requestNumber;
        if (controller) controller.abort();
        controller = new AbortController();
        error.hidden = true;
        results.setAttribute('aria-busy', 'true');
        finderStatus.textContent = 'Finding pieces…';
        const active = doc.activeElement;
        const focusName = active && active.name;
        const focusLabel = active && active.getAttribute('aria-label');
        const selection = active && typeof active.selectionStart === 'number' ? [active.selectionStart, active.selectionEnd] : null;
        try {
            const response = await fetch(url, {credentials: 'same-origin', headers: {'Accept': 'application/json'}, signal: controller.signal});
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                const fields = data.errors ? Object.values(data.errors).flat() : [];
                throw new Error(response.status === 401 ? 'Your session has expired. Reload the page and try again.' : (fields[0] || data.message || 'Unable to find pieces. Please try again.'));
            }
            if (typeof data.html !== 'string') throw new Error('Unable to load the pieces. Please try again.');
            if (current !== requestNumber || !root.isConnected) return;
            results.innerHTML = data.html;
            syncFinder();
            finderStatus.textContent = Number(data.total).toLocaleString() + ' matching pieces. Your playlist selection is unchanged.';
            const focus = Array.from(results.querySelectorAll('input, select, button, a')).find(item => focusName ? item.name === focusName : focusLabel && item.getAttribute('aria-label') === focusLabel);
            if (focus) {
                focus.focus();
                if (selection && typeof focus.setSelectionRange === 'function') focus.setSelectionRange(...selection);
            }
        } catch (failure) {
            if (current !== requestNumber || !root.isConnected || failure.name === 'AbortError') return;
            error.textContent = failure.message || 'Unable to find pieces. Please try again.';
            error.hidden = false;
            finderStatus.textContent = '';
        } finally {
            if (current === requestNumber && root.isConnected) results.setAttribute('aria-busy', 'false');
        }
    }

    if (win.jQuery && win.jQuery.fn.sortable) {
        win.jQuery(body).sortable({
            handle: '.sort-handle', items: '[data-selected-piece]', axis: 'y',
            helper: function (_, row) {
                const cells = row.children(), helper = row.clone();
                helper.children().each(function (index) { win.jQuery(this).width(cells.eq(index).width()); });
                return helper;
            },
            stop: function () { update(true); },
        });
    }
    const cover = form.querySelector('[data-playlist-cover]');
    form.querySelector('[name="cover"]').addEventListener('change', function () {
        const file = this.files[0];
        if (!file || !file.type.startsWith('image/')) return;
        if (coverUrl) URL.revokeObjectURL(coverUrl);
        coverUrl = URL.createObjectURL(file);
        cover.src = coverUrl;
    });
    form.addEventListener('submit', function (event) {
        if (submitting) { event.preventDefault(); return; }
        submitting = true; save.disabled = true; status.textContent = 'Saving changes…';
    });
    win.addEventListener('beforeunload', function (event) {
        if (dirty && !submitting) { event.preventDefault(); event.returnValue = ''; }
    });
    win.addEventListener('pageshow', function () {
        submitting = false; save.disabled = false; update(false);
        status.textContent = dirty ? 'Unsaved changes' : 'All changes saved';
    });
    update(false);
})(document, window);
