(function () {
    'use strict';
    const panel = document.getElementById('notifications-panel');
    if (!panel) return;

    const form = panel.querySelector('[data-notification-read-all]');
    const submit = form.querySelector('button');
    const body = panel.querySelector('[data-notification-body]');
    const summary = panel.querySelector('[data-notification-summary]');
    const error = panel.querySelector('[data-notification-error]');
    let pending = false;

    function render(result) {
        const count = result.unreadCount;
        body.innerHTML = result.html;
        summary.textContent = count ? count + ' unread ' + (count === 1 ? 'notification' : 'notifications') : "You're all caught up";
        if (count > Number(panel.dataset.previewLimit)) summary.textContent += ' · Showing latest ' + panel.dataset.previewLimit;
        form.hidden = count === 0;
        document.querySelectorAll('.notifications-link').forEach(link => {
            link.setAttribute('aria-label', 'Notifications (' + count + ' unread)');
            const badge = link.querySelector('.admin-notification-dot');
            badge.textContent = count;
            badge.hidden = count === 0;
            link.querySelector('.notification-bell').classList.toggle('icon-filled', count > 0);
        });
    }

    async function send(url, button) {
        if (pending) return;
        pending = true;
        submit.disabled = true;
        if (button) button.disabled = true;
        panel.setAttribute('aria-busy', 'true');
        error.hidden = true;
        try {
            const response = await fetch(url, {
                method: 'POST', credentials: 'same-origin', cache: 'no-store',
                headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value},
            });
            if (!response.ok) throw new Error(response.status === 419 || response.status === 401
                ? 'Your session has expired. Reload the page and try again.' : 'Unable to update notifications. Please try again.');
            const result = await response.json();
            if (!Number.isInteger(result.unreadCount) || result.unreadCount < 0 || typeof result.html !== 'string') {
                throw new Error('Unable to update notifications. Please try again.');
            }
            if (!panel.isConnected) return;
            render(result);
            // Keep the visible history rows in sync without refetching the table.
            document.querySelectorAll('#notifications-table button.mark-notification').forEach(action => {
                if (button && action.closest('tr') !== button.closest('tr')) return;
                const read = new URL(action.dataset.url, window.location.href).pathname.endsWith('/read');
                const isRead = !button || new URL(button.dataset.url, window.location.href).pathname.endsWith('/read');
                action.style.display = read === isRead ? 'none' : 'inline-block';
                action.closest('tr').classList.toggle('opacity-4', isRead);
            });
            if (form.hidden && !button && panel.classList.contains('show')) window.bootstrap.Offcanvas.getOrCreateInstance(panel).hide();
        } catch (failure) {
            if (!panel.isConnected) return;
            error.textContent = failure.message || 'Unable to update notifications. Please try again.';
            error.hidden = false;
            // History actions must surface errors even while the drawer is closed.
            if (button) window.alert(error.textContent);
        } finally {
            pending = false;
            submit.disabled = false;
            if (button) button.disabled = false;
            panel.setAttribute('aria-busy', 'false');
        }
    }

    form.addEventListener('submit', event => {
        event.preventDefault();
        send(form.action);
    });
    document.addEventListener('click', event => {
        const button = event.target.closest('button.mark-notification');
        if (!button) return;
        event.preventDefault();
        send(button.dataset.url, button);
    });
}());
