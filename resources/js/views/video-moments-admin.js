(function (doc) {
    'use strict';
    var form = doc.querySelector('[data-moments-editor]');
    if (!form) return;
    var rows = form.querySelector('[data-moment-rows]');
    function reindex() {
        Array.from(rows.children).forEach(function (row, index) {
            row.querySelectorAll('[data-field]').forEach(function (input) {
                input.name = 'moments[' + index + '][' + input.getAttribute('data-field') + ']';
            });
            row.querySelector('[data-moment-action="up"]').disabled = index === 0;
            row.querySelector('[data-moment-action="down"]').disabled = index === rows.children.length - 1;
        });
    }
    form.addEventListener('click', function (event) {
        var button = event.target.closest('[data-moment-action]');
        if (!button) return;
        var action = button.getAttribute('data-moment-action');
        var row = button.closest('[data-moment-row]');
        if (action === 'add') {
            var fragment = form.querySelector('[data-moment-template]').content.cloneNode(true);
            rows.appendChild(fragment);
            rows.lastElementChild.querySelector('[data-field="start_time"]').focus();
        } else if (action === 'delete') row.remove();
        else if (action === 'up' && row.previousElementSibling) rows.insertBefore(row, row.previousElementSibling);
        else if (action === 'down' && row.nextElementSibling) rows.insertBefore(row.nextElementSibling, row);
        reindex();
    });
    reindex();
}(document));
