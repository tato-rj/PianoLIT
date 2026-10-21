(function () {
    const modal = document.getElementById('tag-modal');
    if (!modal) return;
    const form = modal.querySelector('form');
    const method = form.querySelector('[data-tag-method]');
    const save = form.querySelector('[data-tag-save]');
    const creator = form.querySelector('[data-tag-creator]');

    modal.addEventListener('show.bs.modal', function (event) {
        const trigger = event.relatedTarget;
        // An automatic reopen keeps the failed draft and its route-bound identity.
        if (!trigger) return;
        const editing = trigger.hasAttribute('data-tag-edit');
        form.action = editing ? trigger.dataset.editUrl : form.dataset.createUrl;
        method.disabled = !editing;
        modal.querySelector('.modal-title').textContent = editing ? 'Edit tag' : 'Create a tag';
        save.textContent = editing ? 'Save changes' : 'Create tag';
        form.elements.name.value = editing ? trigger.dataset.name : '';
        form.elements.type.value = editing ? trigger.dataset.type : form.dataset.defaultType;
        form.elements.ordering.value = editing ? (trigger.dataset.ordering || '') : '';
        creator.hidden = !editing;
        creator.querySelector('span').textContent = editing ? trigger.dataset.creator : '';
        form.querySelectorAll('.is-invalid').forEach(field => {
            field.classList.remove('is-invalid');
            field.removeAttribute('aria-invalid');
        });
        form.querySelectorAll('.invalid-feedback, [data-tag-errors]').forEach(error => { error.hidden = true; });
    });
    modal.addEventListener('shown.bs.modal', function () { form.elements.name.focus(); });

    const deleteModal = document.getElementById('delete-tag-modal');
    if (deleteModal) deleteModal.addEventListener('show.bs.modal', function (event) {
        const trigger = event.relatedTarget;
        const deleteForm = deleteModal.querySelector('form');
        const submit = deleteForm.querySelector('[type="submit"]');
        submit.disabled = !trigger;
        if (!trigger) { deleteForm.removeAttribute('action'); return; }
        deleteForm.action = trigger.dataset.deleteUrl;
        deleteModal.querySelector('[data-delete-tag-name]').textContent = trigger.dataset.name;
        const count = Number(trigger.dataset.pieces);
        deleteModal.querySelector('[data-delete-tag-pieces]').textContent = count.toLocaleString() + (count === 1 ? ' piece' : ' pieces');
    });

    if (modal.classList.contains('autoshow')) window.bootstrap.Modal.getOrCreateInstance(modal).show();
})();
