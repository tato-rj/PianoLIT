(function () {
    const modal = document.getElementById('freepick-schedule-modal');
    if (!modal) return;
    const form = modal.querySelector('[data-freepick-form]');
    const calendar = form.querySelector('[data-freepick-calendar]');
    const picker = form.querySelector('[data-date-picker]');
    const date = form.elements.namedItem('scheduled_date');
    const hour = form.elements.namedItem('scheduled_hour');
    const scheduled = form.elements.namedItem('scheduled_at');
    const sendEmail = form.querySelector('#freepick-send-email');
    const submit = form.querySelector('[data-freepick-submit]');
    const label = form.querySelector('[data-freepick-submit-label]');
    const error = form.querySelector('[data-freepick-date-error]');
    const blocked = JSON.parse(picker.dataset.blockedDates || '[]');
    const loadedAt = Date.now(), serverTime = Date.parse(form.dataset.now);
    const clock = new Intl.DateTimeFormat('en-CA', {
        timeZone: form.dataset.timezone, year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit', hourCycle: 'h23'
    });
    let submitting = false;
    function current() {
        const parts = {};
        clock.formatToParts(new Date(serverTime + Date.now() - loadedAt)).forEach(part => { parts[part.type] = part.value; });
        return {date: parts.year + '-' + parts.month + '-' + parts.day, time: parts.hour + ':' + parts.minute};
    }
    function update() {
        const later = form.querySelector('[name="mode"]:checked').value === 'later';
        const now = current();
        let minimum = now.date;
        if (now.time >= '23:00') minimum = new Date(Date.parse(now.date + 'T00:00:00Z') + 86400000).toISOString().slice(0, 10);
        calendar.hidden = !later;
        date.disabled = hour.disabled = scheduled.disabled = !later;
        date.required = hour.required = later;
        picker.dataset.minDate = minimum;
        if (picker.datePicker) {
            picker.datePicker.configure(minimum, blocked);
            picker.datePicker.setDisabled(!later);
        }
        Array.from(hour.options).forEach(option => { option.disabled = date.value === now.date && option.value <= now.time; });
        const selectedHour = Array.from(hour.options).find(option => option.value === hour.value);
        if (selectedHour && selectedHour.disabled) {
            const nextHour = Array.from(hour.options).find(option => !option.disabled);
            hour.value = nextHour ? nextHour.value : '';
        }
        let message = '';
        if (later && date.value) {
            if (date.value < minimum) message = 'Choose a date and time in the future.';
            else if (blocked.indexOf(date.value) !== -1) message = 'Another Freepick is already scheduled for this date. Choose a different date.';
        }
        date.setCustomValidity(message);
        error.textContent = message;
        error.hidden = !message;
        scheduled.value = later && date.value && hour.value ? date.value + 'T' + hour.value : '';
        submit.disabled = submitting || (later && (!date.value || !hour.value || !!message));
        label.textContent = later ? 'Schedule Freepick' : 'Make Freepick now';
    }
    form.querySelectorAll('[name="mode"]').forEach(input => input.addEventListener('change', update));
    date.addEventListener('change', update);
    hour.addEventListener('change', update);
    modal.addEventListener('show.bs.modal', function (event) {
        const trigger = event.relatedTarget;
        if (!trigger) { update(); return; }
        form.action = trigger.dataset.freepickUrl;
        form.querySelector('[data-freepick-title]').textContent = trigger.dataset.freepickName;
        form.querySelector('[value="now"]').checked = true;
        form.querySelector('[value="later"]').checked = false;
        if (picker.datePicker) picker.datePicker.setValue('');
        else date.value = '';
        hour.value = '08:00';
        sendEmail.checked = true;
        form.querySelectorAll('.invalid-feedback, [data-freepick-error]').forEach(item => { item.hidden = true; });
        date.classList.remove('is-invalid');
        submitting = false;
        update();
    });
    form.addEventListener('submit', function (event) {
        if (submitting) { event.preventDefault(); return; }
        update();
        if (submit.disabled || !form.getAttribute('action') || !form.checkValidity()) { event.preventDefault(); return; }
        submitting = true;
        submit.disabled = true;
    });
    update();
    document.addEventListener('DOMContentLoaded', update);
    window.addEventListener('pageshow', function () { submitting = false; update(); });
    if (form.dataset.restore === 'true') window.bootstrap.Modal.getOrCreateInstance(modal).show();
})();
