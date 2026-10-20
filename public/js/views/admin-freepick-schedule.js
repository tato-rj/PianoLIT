(function () {
    const modal = document.getElementById('freepick-schedule-modal');
    if (!modal) return;
    const form = modal.querySelector('[data-freepick-form]');
    const calendar = form.querySelector('[data-freepick-calendar]');
    const date = form.elements.namedItem('scheduled_at');
    const submit = form.querySelector('[data-freepick-submit]');
    const label = form.querySelector('[data-freepick-submit-label]');
    let submitting = false;
    function update() {
        const later = form.querySelector('[name="mode"]:checked').value === 'later';
        calendar.hidden = !later;
        date.disabled = !later;
        date.required = later;
        label.textContent = later ? 'Schedule Freepick' : 'Make Freepick now';
    }
    form.querySelectorAll('[name="mode"]').forEach(input => input.addEventListener('change', update));
    modal.addEventListener('show.bs.modal', function (event) {
        const trigger = event.relatedTarget;
        if (!trigger) return;
        form.action = trigger.dataset.freepickUrl;
        form.querySelector('[data-freepick-title]').textContent = trigger.dataset.freepickName;
        form.querySelector('[value="now"]').checked = true;
        form.querySelector('[value="later"]').checked = false;
        date.value = '';
        form.querySelectorAll('.invalid-feedback, [data-freepick-error]').forEach(error => { error.hidden = true; });
        date.classList.remove('is-invalid');
        submit.disabled = false;
        submitting = false;
        update();
    });
    form.addEventListener('submit', function (event) {
        if (submitting || !form.getAttribute('action') || !form.checkValidity()) { event.preventDefault(); return; }
        submitting = true;
        submit.disabled = true;
    });
    update();
    window.addEventListener('pageshow', function () { submitting = false; submit.disabled = false; });
    if (form.dataset.restore === 'true') window.bootstrap.Modal.getOrCreateInstance(modal).show();
})();
