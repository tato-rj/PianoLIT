(function ($) {
    'use strict';
    $(function () {
        var $table = $('#pieces-table');
        if (!$table.length) return;
        var $filters = $('[data-piece-table-filters]');
        var $error = $('[data-piece-table-error]');
        function showTable() {
            $table.find('thead').removeClass('invisible');
            $table.closest('.dataTables_wrapper').removeClass('table-loading');
        }
        $table.on('xhr.dt', function (event, settings, json, xhr) {
            // A failed older request must not replace the current draw's status.
            if (xhr !== settings.jqXHR) return true;
            $error.prop('hidden', !!json && !json.error);
            if (!json || json.error) {
                showTable();
                return true; // DataTables clears processing without a second alert.
            }
        });
        var table = $table.DataTable({
            processing: true,
            serverSide: true,
            order: [],
            searchDelay: 350,
            language: {processing: '<div class="overlay-pulse"></div>'},
            ajax: {
                url: window.location.href,
                data: function (data) {
                    $filters.find('input[type="checkbox"]').each(function () {
                        data[this.name] = this.checked ? 1 : 0;
                    });
                }
            },
            columns: [
                {data: 'id'},
                {data: 'name', className: 'dataTables_main_column'},
                {data: 'composer.short_name', className: 'text-nowrap'},
                {data: 'tags'},
                {data: 'level'},
                {data: 'ranking'},
                {data: 'favorited'},
                {data: 'actions', orderable: false, searchable: false}
            ],
            initComplete: showTable
        });
        $(table.table().container()).addClass('table-loading');
        $filters.on('change', 'input[type="checkbox"]', function () {
            table.ajax.reload(null, true);
        });
        $('[data-piece-table-retry]').on('click', function () {
            table.ajax.reload(null, false);
        });
    });
}(jQuery));
