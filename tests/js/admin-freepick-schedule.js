const assert = require('assert');
const fs = require('fs');
const vm = require('vm');
const datePicker = require('../../resources/js/components/date-picker');

function fixture(serverNow = '2026-10-20T09:00:00-04:00') {
    function node(properties = {}) {
        return Object.assign({listeners: {}, addEventListener(event, callback) { this.listeners[event] = callback; }}, properties);
    }
    const now = node({value: 'now', checked: true});
    const later = node({value: 'later', checked: false});
    const date = node({value: '2026-10-22', disabled: false, required: false, classList: {remove() {}},
        setCustomValidity(message) { this.validationMessage = message; }});
    const hour = node({value: '08:00', options: Array.from({length: 24}, (_, i) => ({value: String(i).padStart(2, '0') + ':00'}))});
    const scheduled = {}, calendar = {}, submit = {}, label = {}, title = {}, error = {hidden: false}, dateError = {}, sendEmail = {checked: false};
    const picker = {dataset: {blockedDates: '["2026-10-23"]'}, datePicker: {
        configure(minimum) { this.minimum = minimum; }, setDisabled(disabled) { this.disabled = disabled; },
        setValue(value) { date.value = value; }
    }};
    const nodes = {'[data-freepick-calendar]': calendar, '[data-date-picker]': picker, '[data-freepick-submit]': submit,
        '[data-freepick-submit-label]': label, '[data-freepick-title]': title, '[data-freepick-date-error]': dateError,
        '[value="now"]': now, '[value="later"]': later, '#freepick-send-email': sendEmail};
    const fields = {scheduled_date: date, scheduled_hour: hour, scheduled_at: scheduled};
    let valid = true, restored = 0, elapsed = 0;
    class ClockDate extends Date { static now() { return 100000 + elapsed; } }
    const form = node({action: '/pieces/1/schedule', dataset: {restore: 'true', now: serverNow, timezone: 'America/New_York'},
        elements: {namedItem(name) { return fields[name]; }},
        querySelector(selector) { return selector === '[name="mode"]:checked' ? (later.checked ? later : now) : nodes[selector]; },
        querySelectorAll(selector) { return selector === '[name="mode"]' ? [now, later] : [error]; },
        getAttribute() { return this.action; }, checkValidity() { return valid && !date.validationMessage; }});
    const modal = node({querySelector() { return form; }});
    const window = node({bootstrap: {Modal: {getOrCreateInstance() { return {show() { restored++; }}; }}}});
    vm.runInNewContext(fs.readFileSync('resources/js/views/admin-freepick-schedule.js', 'utf8'), {
        document: {getElementById() { return modal; }, addEventListener() {}}, window, Date: ClockDate, Intl
    });
    return {now, later, date, hour, scheduled, calendar, picker, submit, label, title, error, dateError, form, modal, window,
        restored, sendEmail, validity(value) { valid = value; }, advance(ms) { elapsed += ms; },
        laterMode() { now.checked = false; later.checked = true; later.listeners.change(); }};
}

module.exports = function () {
    assert.strictEqual(datePicker.available('2026-10-19', '2026-10-20', []), false);
    assert.strictEqual(datePicker.available('2026-10-23', '2026-10-20', ['2026-10-23']), false);
    assert.strictEqual(datePicker.available('2026-10-24', '2026-10-20', ['2026-10-23']), true);
    assert.strictEqual(datePicker.available('2026-02-31', '', []), false);
    assert.strictEqual(datePicker.parse('2026-11-01').getDate(), 1, 'Calendar dates are independent of schedule DST instants.');
    const f = fixture();
    assert.strictEqual(f.restored, 1, 'A server validation failure reopens the modal.');
    assert.strictEqual(f.sendEmail.checked, false, 'A restored unchecked email choice is preserved.');
    assert.strictEqual(f.date.disabled, true, 'Immediate selection omits stale calendar input.');
    assert.strictEqual(f.hour.value, '08:00', 'The default time is 8am.');
    f.laterMode();
    assert.strictEqual(f.calendar.hidden, false);
    assert.strictEqual(f.date.required, true);
    assert.strictEqual(f.hour.disabled, false);
    assert.strictEqual(f.label.textContent, 'Schedule Freepick');
    assert.strictEqual(f.scheduled.value, '2026-10-22T08:00');
    f.date.value = '2026-10-19'; f.date.listeners.change();
    assert.strictEqual(f.submit.disabled, true);
    assert(f.dateError.textContent.includes('future'));
    f.date.value = '2026-10-23'; f.date.listeners.change();
    assert.strictEqual(f.submit.disabled, true);
    assert(f.dateError.textContent.includes('already scheduled'));
    f.date.value = '2026-10-20'; f.date.listeners.change();
    assert.strictEqual(f.hour.options[8].disabled, true);
    assert.strictEqual(f.hour.options[9].disabled, true, 'An hour at the present instant is not in the future.');
    assert.strictEqual(f.hour.value, '10:00');
    f.advance(3600000); f.window.listeners.pageshow();
    assert.strictEqual(f.hour.value, '11:00', 'A page left open cannot submit an elapsed hour.');
    f.date.value = '2026-10-22'; f.date.listeners.change();
    let prevented = 0;
    f.validity(false); f.form.listeners.submit({preventDefault() { prevented++; }});
    assert.strictEqual(prevented, 1);
    f.validity(true); f.form.listeners.submit({preventDefault() { prevented++; }});
    assert.strictEqual(f.submit.disabled, true);
    f.form.listeners.submit({preventDefault() { prevented++; }});
    assert.strictEqual(prevented, 2, 'Repeated keyboard submission cannot schedule twice.');
    f.modal.listeners['show.bs.modal']({relatedTarget: {dataset: {freepickUrl: '/pieces/2/schedule?sort=id_asc', freepickName: '<script>Piece two</script>'}}});
    assert.strictEqual(f.form.action, '/pieces/2/schedule?sort=id_asc');
    assert.strictEqual(f.title.textContent, '<script>Piece two</script>', 'Titles are inserted as text.');
    assert.strictEqual(f.now.checked, true);
    assert.strictEqual(f.later.checked, false);
    assert.strictEqual(f.date.value, '');
    assert.strictEqual(f.hour.value, '08:00');
    assert.strictEqual(f.sendEmail.checked, true, 'Each new modal selection defaults to automatically sending the email.');
    assert.strictEqual(f.error.hidden, true);
    assert.strictEqual(f.submit.disabled, false);
    f.window.listeners.pageshow();
    assert.strictEqual(f.submit.disabled, false);
    const late = fixture('2026-10-20T23:05:00-04:00');
    late.laterMode();
    assert.strictEqual(late.picker.dataset.minDate, '2026-10-21', 'A day with no future whole hours is unavailable.');
    console.log('Passed: Freepick whole hours, 8am default, booked/past dates, clock rollover, restored errors and duplicate submissions.');
};
if (require.main === module) module.exports();
