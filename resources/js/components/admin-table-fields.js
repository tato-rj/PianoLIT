// Applied after DataTables loads, before any admin table is initialized.
// Native search/select controls share the default PianoLIT field classes.
(function ($) {
    $.extend($.fn.dataTable.ext.classes, {
        sFilterInput: 'form-control',
        sLengthSelect: 'form-select'
    });
    // Some pages load a legacy Bootstrap adapter after the admin bundle. Apply
    // the shared classes before its first draw too, without replacing handlers.
    $(document).on('preInit.dt.pianolitFields', function (event, settings) {
        var $container = $(settings.nTableWrapper);
        $container.find('.dataTables_filter input')
            .removeClass('form-control-sm').addClass('form-control');
        $container.find('.dataTables_length select')
            .removeClass('custom-select custom-select-sm form-control form-control-sm')
            .addClass('form-select');
    });
})(window.jQuery);
