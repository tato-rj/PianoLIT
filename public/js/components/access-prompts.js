module.exports = function (win) {
    const doc = win.document;
    doc.addEventListener('show.bs.modal', event => {
        const modal = event.target;
        if (modal.getAttribute('data-access-prompt') !== 'premium') return;
        let copy;
        try { copy = JSON.parse(modal.getAttribute('data-access-copy') || '{}'); }
        catch (error) { return; }
        if (!copy || typeof copy !== 'object' || Array.isArray(copy)) return;
        const trigger = event.relatedTarget && event.relatedTarget.closest('[data-access-action]');
        const action = (trigger && trigger.getAttribute('data-access-action')) || modal.getAttribute('data-access-action') || 'default';
        modal.removeAttribute('data-access-action');
        const entry = Object.prototype.hasOwnProperty.call(copy, action) ? copy[action] : copy.default;
        if (!entry || typeof entry.title !== 'string' || typeof entry.description !== 'string') return;
        modal.querySelector('[data-access-title]').textContent = entry.title;
        modal.querySelector('[data-access-description]').textContent = entry.description;
    });
    const api = {show(target, action, opener) {
        const modal = typeof target === 'string' ? doc.querySelector(target) : target;
        if (!modal) return false;
        modal.setAttribute('data-access-action', action || 'default');
        win.bootstrap.Modal.getOrCreateInstance(modal).show(opener);
        return true;
    }};
    win.PianoLITAccessPrompts = api;
    return api;
};
