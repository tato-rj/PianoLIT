(function () {
    'use strict';
    var modal = document.getElementById('piece-quick-edit');
    if (!modal) return;
    var content = modal.querySelector('[data-piece-edit-content]');
    var status = modal.querySelector('[data-piece-edit-status]');
    var retry = modal.querySelector('[data-piece-edit-retry]');
    var trigger, request, generation = 0, saving = false;

    function message(text, failed) {
        status.textContent = text;
        status.classList.toggle('text-danger', !!failed);
        status.classList.toggle('text-muted', !failed);
    }
    async function load() {
        var current = ++generation;
        if (request) request.abort();
        request = new AbortController();
        content.replaceChildren();
        content.setAttribute('aria-busy', 'true');
        retry.hidden = true;
        message('Loading…');
        try {
            var response = await fetch(trigger.dataset.pieceEditUrl, {
                headers: {'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest'},
                credentials: 'same-origin', signal: request.signal
            });
            if (!response.ok || response.redirected) throw new Error('load');
            var html = await response.text();
            if (current !== generation) return;
            content.innerHTML = html;
            message('Changes save automatically.');
        } catch (error) {
            if (current !== generation || error.name === 'AbortError') return;
            message('The editor could not be loaded. Please try again.', true);
            retry.hidden = false;
        } finally {
            if (current === generation) content.removeAttribute('aria-busy');
        }
    }
    modal.addEventListener('show.bs.modal', function (event) {
        trigger = event.relatedTarget;
        modal.querySelector('.modal-title').textContent = trigger.dataset.pieceEditTitle;
        load();
    });
    modal.addEventListener('hide.bs.modal', function (event) {
        // Finish the account's write before allowing another piece editor to open.
        if (saving) event.preventDefault();
    });
    modal.addEventListener('hidden.bs.modal', function () {
        generation++;
        if (request) request.abort();
        content.replaceChildren();
    });
    retry.addEventListener('click', load);
    content.addEventListener('change', async function (event) {
        var input = event.target;
        var group = input.closest('[data-piece-level], [data-piece-tags]');
        if (!group || saving || input.tagName !== 'INPUT') return;
        var isLevel = group.hasAttribute('data-piece-level');
        var oldLevel = group.dataset.oldLevel;
        var controls = Array.from(content.querySelectorAll('input'));
        var body = isLevel ? {old_level_id: oldLevel || null, new_level_id: input.value} : {id: input.value};
        saving = true;
        controls.forEach(function (control) { control.disabled = true; });
        message('Saving…');
        try {
            var response = await fetch(group.dataset.url, {
                method: 'PATCH', credentials: 'same-origin',
                headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.app.csrfToken},
                body: JSON.stringify(body)
            });
            if (!response.ok || response.redirected) throw new Error('save');
            var data = await response.json();
            var badge = document.getElementById(group.dataset.badge);
            if (isLevel) {
                group.dataset.oldLevel = String(data.level_id);
                badge.querySelector('[data-piece-level-label]').textContent = data.extended_level_name || data.level_name;
            } else {
                badge.querySelector('[data-piece-tags-count]').textContent = Math.max(0, data.count);
            }
            message('Saved.');
        } catch (error) {
            if (isLevel) controls.forEach(function (control) { control.checked = control.value === oldLevel; });
            else input.checked = !input.checked;
            message('The change could not be saved. Please try again, or reopen the editor to refresh it.', true);
        } finally {
            saving = false;
            controls.forEach(function (control) { control.disabled = false; });
        }
    });
}());
