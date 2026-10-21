(function () {
    'use strict';
    const root = document.querySelector('[data-playlist-tags]');
    if (!root) return;
    const create = root.querySelector('[data-tag-create]');
    const list = root.querySelector('[data-tag-list]');
    const error = root.querySelector('[data-tag-error]');
    const status = root.querySelector('[data-tag-status]');
    let editing = null, pending = false;

    function controls() {
        root.querySelectorAll('input, button').forEach(control => {
            const row = control.closest('[data-tag-id]');
            control.disabled = pending || (editing !== null && (!row || row.dataset.tagId !== editing));
        });
        root.setAttribute('aria-busy', pending ? 'true' : 'false');
    }
    function clearError() {
        error.hidden = true;
        error.textContent = '';
        root.querySelectorAll('[aria-invalid]').forEach(input => {
            input.removeAttribute('aria-invalid');
            input.classList.remove('is-invalid');
        });
    }
    function edit(row, active) {
        editing = active ? row.dataset.tagId : null;
        row.querySelector('[data-tag-label]').hidden = active;
        row.querySelector('[data-tag-read-actions]').hidden = active;
        row.querySelector('[data-tag-edit-actions]').hidden = !active;
        row.querySelector('[data-tag-edit]').hidden = !active;
        const input = row.querySelector('[name="name"]');
        input.value = row.dataset.tagName;
        clearError();
        status.textContent = '';
        controls();
        if (active) { input.focus(); input.select(); }
        else row.querySelector('[data-tag-start-edit]').focus();
    }
    function syncTags(tags) {
        const names = new Map(tags.map(tag => [String(tag.id), tag.name]));
        document.querySelectorAll('.js-playlist-tag-select').forEach(select => {
            const selected = select.value;
            select.replaceChildren();
            [['', 'No tag'], ...tags.map(tag => [String(tag.id), tag.name])].forEach(([value, name]) => {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = name;
                select.appendChild(option);
            });
            select.value = names.has(selected) ? selected : '';
        });
        document.querySelectorAll('[data-playlist-featured-tag]').forEach(label => {
            if (!label.dataset.playlistFeaturedTag) return;
            const name = names.get(label.dataset.playlistFeaturedTag);
            label.textContent = name || '';
            label.hidden = !name;
        });
    }
    async function send(url, method, form, row) {
        if (pending) return;
        pending = true;
        clearError();
        status.textContent = 'Saving changes…';
        controls();
        try {
            const response = await fetch(url, {
                method, credentials: 'same-origin',
                headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': root.dataset.csrf},
                body: method === 'DELETE' ? undefined : JSON.stringify({name: form.querySelector('[name="name"]').value}),
            });
            let result;
            try { result = await response.json(); }
            catch (_) { throw new Error('Unable to save tags. Please try again.'); }
            if (!response.ok) {
                const message = response.status === 419 ? 'Your session has expired. Reload the page and try again.'
                    : (result.errors && result.errors.name ? result.errors.name[0] : result.message);
                if (form && result.errors && result.errors.name) {
                    const input = form.querySelector('[name="name"]');
                    input.classList.add('is-invalid'); input.setAttribute('aria-invalid', 'true');
                }
                throw new Error(message || 'Unable to save tags. Please try again.');
            }
            if (!Array.isArray(result.tags) || typeof result.html !== 'string') throw new Error('Unable to update the tag list. Please try again.');
            if (!root.isConnected) return;
            const changedId = row ? row.dataset.tagId : null;
            editing = null;
            list.innerHTML = result.html;
            syncTags(result.tags);
            if (form === create) create.reset();
            status.textContent = result.message;
            pending = false;
            controls();
            const changedRow = changedId ? list.querySelector('[data-tag-id="' + changedId + '"]') : null;
            if (changedRow) changedRow.querySelector('[data-tag-start-edit]').focus();
            else create.querySelector('[name="name"]').focus();
        } catch (failure) {
            if (!root.isConnected) return;
            error.textContent = failure.message || 'Unable to save tags. Please try again.';
            error.hidden = false;
            status.textContent = '';
        } finally {
            pending = false;
            if (root.isConnected) controls();
        }
    }
    root.addEventListener('submit', function (event) {
        const form = event.target;
        if (!form.matches('[data-tag-create], [data-tag-edit]')) return;
        event.preventDefault();
        const row = form.closest('[data-tag-id]');
        send(form.action, row ? 'PATCH' : 'POST', form, row);
    });
    root.addEventListener('click', function (event) {
        const button = event.target.closest('[data-tag-start-edit], [data-tag-cancel], [data-tag-delete]');
        if (!button || button.disabled || pending) return;
        const row = button.closest('[data-tag-id]');
        if (button.hasAttribute('data-tag-start-edit')) edit(row, true);
        else if (button.hasAttribute('data-tag-cancel')) edit(row, false);
        else if (window.confirm('Delete “' + row.dataset.tagName + '”? This removes the tag from all associated playlists.')) {
            send(button.dataset.tagDelete, 'DELETE', null, row);
        }
    });
})();
