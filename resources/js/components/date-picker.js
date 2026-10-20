(function () {
    'use strict';

    function available(value, minimum, blocked) {
        return parse(value) !== null && (!minimum || value >= minimum) && blocked.indexOf(value) === -1;
    }
    function parse(value) {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) return null;
        var parts = value.split('-').map(Number), date = new Date(parts[0], parts[1] - 1, parts[2]);
        return date.getFullYear() === parts[0] && date.getMonth() === parts[1] - 1 && date.getDate() === parts[2] ? date : null;
    }
    function install(doc, $) {
        if (!$ || !$.fn.datepicker) return;
        doc.querySelectorAll('[data-date-picker]').forEach(function (root) {
            if (root.datePicker) return;
            var input = root.querySelector('input'), calendar = root.querySelector('[data-date-picker-calendar]');
            var minimum = root.dataset.minDate || '', blocked = JSON.parse(root.dataset.blockedDates || '[]');
            function decorate() {
                $(calendar).find('.ui-datepicker-prev, .ui-datepicker-next').addClass('btn btn-secondary btn-sm')
                    .attr('role', 'button');
                $(calendar).find('.ui-datepicker-prev').attr('aria-label', 'Previous month');
                $(calendar).find('.ui-datepicker-next').attr('aria-label', 'Next month');
                $(calendar).find('.ui-datepicker-header .ui-state-disabled').addClass('disabled').attr('aria-disabled', 'true');
                $(calendar).find('td a, td span').addClass('btn btn-light btn-sm');
                $(calendar).find('td .ui-state-active').removeClass('btn-light').addClass('btn-primary').attr('aria-pressed', 'true');
                $(calendar).find('td.ui-datepicker-today a').attr('aria-current', 'date');
                $(calendar).find('.ui-state-disabled span').addClass('disabled').attr('aria-disabled', 'true');
                $(calendar).find('td a').attr('role', 'button');
            }
            function decorateAfterDraw() { Promise.resolve().then(decorate); }
            $(calendar).datepicker({
                dateFormat: 'yy-mm-dd', minDate: parse(minimum), defaultDate: parse(input.value) || parse(minimum),
                prevText: '‹', nextText: '›', disabled: input.disabled,
                beforeShowDay: function (date) {
                    var value = $.datepicker.formatDate('yy-mm-dd', date), selectable = available(value, minimum, blocked);
                    return [selectable, '', selectable ? '' : 'Unavailable date'];
                },
                onSelect: function (value) {
                    if (!available(value, minimum, blocked)) return;
                    input.value = value;
                    input.dispatchEvent(new Event('change', {bubbles: true}));
                    decorateAfterDraw();
                },
                onChangeMonthYear: decorateAfterDraw
            });
            $(calendar).on('keydown', 'a', function (event) {
                if (event.key === ' ') { event.preventDefault(); this.click(); }
            });
            root.datePicker = {
                configure: function (nextMinimum, nextBlocked) {
                    minimum = nextMinimum; blocked = nextBlocked;
                    $(calendar).datepicker('option', 'minDate', parse(minimum)); decorate();
                },
                setDisabled: function (disabled) {
                    input.disabled = disabled;
                    $(calendar).datepicker('option', 'disabled', disabled); decorate();
                },
                setValue: function (value) {
                    input.value = value;
                    $(calendar).datepicker('setDate', parse(value)); decorate();
                },
                available: function (value) { return available(value, minimum, blocked); }
            };
            if (input.value) $(calendar).datepicker('setDate', parse(input.value));
            decorate();
        });
    }
    if (typeof module !== 'undefined') module.exports = {available: available, parse: parse, install: install};
    if (typeof document !== 'undefined') {
        var start = function () { install(document, window.jQuery); };
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
        else start();
    }
}());
