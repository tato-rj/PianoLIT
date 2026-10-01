(function (doc, win) {
    'use strict';
    var form = doc.querySelector('[data-moments-editor]');
    if (!form) return;
    var rows = form.querySelector('[data-moment-rows]');
    var alert = form.querySelector('[data-moment-alert]');
    var sorting = false;
    var media = doc.querySelector('[data-moment-preview] video');
    if (media && win.Plyr && win.VideoMoments) {
        var player = new win.Plyr(media, Object.assign({ratio: '16:9'}, win.VideoMoments.markerOptions(media)));
        win.VideoMoments.attach(player, media);
    }
    function parseTime(value) {
        var match = /^(\d{2,7}):([0-5]\d)(?:\.(\d{1,3}))?$/.exec(value.trim());
        return match ? Number(match[1]) * 60000 + Number(match[2]) * 1000 + Number((match[3] || '').padEnd(3, '0')) : null;
    }
    function formatTime(milliseconds) {
        var fraction = milliseconds % 1000;
        return String(Math.floor(milliseconds / 60000)).padStart(2, '0') + ':' +
            String(Math.floor(milliseconds / 1000) % 60).padStart(2, '0') +
            (fraction ? '.' + String(fraction).padStart(3, '0').replace(/0+$/, '') : '');
    }
    function maskTimes(row) {
        win.jQuery(row.querySelectorAll('[data-moment-time]')).inputmask({
            mask: [2, 3, 4, 5, 6, 7].map(function (digits) { return '9'.repeat(digits) + ':s9[.9{1,3}]'; }),
            definitions: {s: {validator: '[0-5]'}},
            keepStatic: true,
            greedy: false,
            showMaskOnHover: false,
            clearIncomplete: false,
            // Inputmask emits jQuery events while handling keystrokes, so also
            // validate through its callbacks after the masked value is updated.
            onKeyValidation: validate,
            oncomplete: validate,
            onincomplete: validate,
            oncleared: validate
        });
    }
    function validate() {
        var messages = [];
        function error(input, message) {
            input.setCustomValidity(message);
            input.classList.add('is-invalid');
            input.setAttribute('aria-invalid', 'true');
            messages.push(message);
        }
        Array.from(rows.children).forEach(function (row, index) {
            var start = row.querySelector('[data-field="start_time"]');
            var end = row.querySelector('[data-field="end_time"]');
            [start, end].forEach(function (input) {
                input.setCustomValidity('');
                input.classList.remove('is-invalid');
                input.removeAttribute('aria-invalid');
            });
            var startTime = parseTime(start.value);
            var endTime = end.value.trim() ? parseTime(end.value) : null;
            if (start.value && startTime === null) error(start, 'Section ' + (index + 1) + ': enter a complete start time as MM:SS.');
            if (end.value && (endTime === null || (startTime !== null && endTime < startTime))) {
                error(end, 'Section ' + (index + 1) + ': enter an end time at or after the start time.');
            }
        });
        alert.textContent = Array.from(new Set(messages)).join(' ');
        alert.hidden = !messages.length;
        return !messages.length;
    }
    function reindex() {
        Array.from(rows.children).forEach(function (row, index) {
            row.querySelectorAll('[data-field]').forEach(function (input) {
                input.name = 'moments[' + index + '][' + input.getAttribute('data-field') + ']';
            });
            row.querySelector('[data-moment-number]').textContent = 'Section ' + (index + 1);
        });
        validate();
    }
    function sortRows() {
        if (sorting) return;
        sorting = true;
        var focused = doc.activeElement;
        var ordered = Array.from(rows.children).map(function (row, index) {
            return {row: row, index: index, time: parseTime(row.querySelector('[data-field="start_time"]').value)};
        }).sort(function (left, right) {
            var leftTime = left.time === null ? Infinity : left.time;
            var rightTime = right.time === null ? Infinity : right.time;
            return (leftTime - rightTime) || (left.index - right.index);
        });
        if (ordered.some(function (item, index) { return item.row !== rows.children[index]; })) {
            ordered.forEach(function (item) { rows.appendChild(item.row); });
            if (focused && rows.contains(focused)) focused.focus({preventScroll: true});
        }
        reindex();
        sorting = false;
    }
    function stepTime(row, field, direction) {
        var input = row.querySelector('[data-field="' + field + '"]');
        var start = parseTime(row.querySelector('[data-field="start_time"]').value) || 0;
        var current = parseTime(input.value);
        if (current === null && input.value.trim()) return validate();
        if (current === null) current = field === 'end_time' ? start : 0;
        var minimum = field === 'end_time' ? start : 0;
        var next = Math.min(9999999999, Math.max(minimum, current + direction * 1000));
        win.jQuery(input).inputmask('setvalue', formatTime(next));
        if (field === 'start_time') sortRows();
        else validate();
    }
    form.addEventListener('click', function (event) {
        var button = event.target.closest('[data-moment-action]');
        if (!button) return;
        var action = button.getAttribute('data-moment-action');
        var row = button.closest('[data-moment-row]');
        if (action === 'add') {
            sortRows();
            var previous = rows.lastElementChild;
            var previousEnd = previous && previous.querySelector('[data-field="end_time"]').value;
            var fragment = form.querySelector('[data-moment-template]').content.cloneNode(true);
            rows.appendChild(fragment);
            var start = rows.lastElementChild.querySelector('[data-field="start_time"]');
            start.value = !previous ? '00:00' : (parseTime(previousEnd) !== null ? previousEnd : '');
            maskTimes(rows.lastElementChild);
        } else if (action === 'delete') row.remove();
        else if (action === 'step') return stepTime(row, button.getAttribute('data-time-field'), Number(button.getAttribute('data-time-step')));
        sortRows();
        if (action === 'add') start.focus();
    });
    form.addEventListener('input', validate);
    form.addEventListener('change', validate);
    // Sort when leaving a row, after keyboard editing finishes. Row actions sort
    // after their click so moving a card cannot swallow a chevron/delete click.
    form.addEventListener('focusout', function (event) {
        var row = event.target.closest('[data-moment-row]');
        if (event.relatedTarget && event.relatedTarget.closest('[data-moment-action]')) return;
        if (row && (!event.relatedTarget || !row.contains(event.relatedTarget))) sortRows();
    });
    form.addEventListener('keydown', function (event) {
        var field = event.target.getAttribute('data-field');
        if ((field === 'start_time' || field === 'end_time') && (event.key === 'ArrowUp' || event.key === 'ArrowDown')) {
            event.preventDefault();
            event.stopPropagation();
            stepTime(event.target.closest('[data-moment-row]'), field, event.key === 'ArrowUp' ? 1 : -1);
        }
    }, true);
    form.addEventListener('submit', function (event) {
        sortRows();
        var valid = validate();
        if (!form.reportValidity() || !valid) event.preventDefault();
    });
    Array.from(rows.children).forEach(maskTimes);
    sortRows();
}(document, window));
