(function (doc, win) {
    'use strict';
    var form = doc.querySelector('[data-moments-editor]');
    if (!form) return;
    var rows = form.querySelector('[data-moment-rows]');
    var alert = form.querySelector('[data-moment-alert]');
    var media = doc.querySelector('[data-moment-preview] video');
    if (media && win.Plyr && win.VideoMoments) {
        var player = new win.Plyr(media, Object.assign({ratio: '16:9'}, win.VideoMoments.markerOptions(media)));
        win.VideoMoments.attach(player, media);
    }
    function parseTime(value) {
        var match = /^(\d{2,7}):([0-5]\d)(?:\.(\d{1,3}))?$/.exec(value.trim());
        return match ? Number(match[1]) * 60000 + Number(match[2]) * 1000 + Number((match[3] || '').padEnd(3, '0')) : null;
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
            if (start.value && startTime === null) error(start, 'Moment ' + (index + 1) + ': enter a complete start time as MM:SS.');
            if (end.value && (endTime === null || (startTime !== null && endTime < startTime))) {
                error(end, 'Moment ' + (index + 1) + ': enter an end time at or after the start time.');
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
            row.querySelector('[data-moment-action="up"]').disabled = index === 0;
            row.querySelector('[data-moment-action="down"]').disabled = index === rows.children.length - 1;
        });
        validate();
    }
    form.addEventListener('click', function (event) {
        var button = event.target.closest('[data-moment-action]');
        if (!button) return;
        var action = button.getAttribute('data-moment-action');
        var row = button.closest('[data-moment-row]');
        if (action === 'add') {
            var previous = rows.lastElementChild;
            var previousEnd = previous && previous.querySelector('[data-field="end_time"]').value;
            var fragment = form.querySelector('[data-moment-template]').content.cloneNode(true);
            rows.appendChild(fragment);
            var start = rows.lastElementChild.querySelector('[data-field="start_time"]');
            start.value = !previous ? '00:00' : (parseTime(previousEnd) !== null ? previousEnd : '');
            maskTimes(rows.lastElementChild);
        } else if (action === 'delete') row.remove();
        else if (action === 'up' && row.previousElementSibling) rows.insertBefore(row, row.previousElementSibling);
        else if (action === 'down' && row.nextElementSibling) rows.insertBefore(row.nextElementSibling, row);
        reindex();
        if (action === 'add') start.focus();
    });
    form.addEventListener('input', validate);
    form.addEventListener('change', validate);
    form.addEventListener('submit', function (event) {
        var valid = validate();
        if (!form.reportValidity() || !valid) event.preventDefault();
    });
    Array.from(rows.children).forEach(maskTimes);
    reindex();
}(document, window));
