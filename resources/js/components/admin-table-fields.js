// Render legacy DataTables controls through the same Blade form as native lists.
// Move the original feature nodes/inputs so DataTables keeps all event handlers,
// state restoration, server-side request contracts and redraw/error behavior.
(function ($) {
    'use strict';
    var defaults = document.querySelector('[data-admin-table-filter-template]');
    if (defaults) $.extend($.fn.dataTable.defaults, {
        pageLength: Number(defaults.dataset.pageSize),
        lengthMenu: JSON.parse(defaults.dataset.pageSizes)
    });
    $.extend($.fn.dataTable.ext.classes, {
        sFilterInput: 'form-control pianolit-input',
        sLengthSelect: 'form-control'
    });

    $(document).on('preInit.dt.pianolitPagination', function (event, settings) {
        var language = settings.oInit.language || settings.oInit.oLanguage || {};
        if (language.url || language.sUrl) return;
        if (language.info === undefined && language.sInfo === undefined) settings.oLanguage.sInfo = 'Showing _START_–_END_ of _TOTAL_ results';
        if (language.infoEmpty === undefined && language.sInfoEmpty === undefined) settings.oLanguage.sInfoEmpty = 'Showing 0–0 of 0 results';
        var paginate = language.paginate || language.oPaginate || {};
        function arrow(icon, label) {
            return '<i class="app-icon icon-' + icon + '" aria-hidden="true"></i><span class="visually-hidden">' + label + '</span>';
        }
        if (paginate.previous === undefined && paginate.sPrevious === undefined) settings.oLanguage.oPaginate.sPrevious = arrow('chevron-left', 'Previous page');
        if (paginate.next === undefined && paginate.sNext === undefined) settings.oLanguage.oPaginate.sNext = arrow('chevron-right', 'Next page');
    });

    // Keep DataTables' original info/pager nodes and their handlers. Bootstrap
    // renderers already share a footer row; older renderers need one wrapper.
    $(document).on('init.dt.pianolitPagination', function (event, settings) {
        var wrapper = settings.nTableWrapper;
        if (!wrapper) return;
        var info = wrapper.querySelector('.dataTables_info');
        var pager = wrapper.querySelector('.dataTables_paginate');
        if (!info && !pager) return;
        var row = (info || pager).closest('.row');
        if (row && row.parentElement === wrapper && (!pager || row.contains(pager))) {
            row.classList.add('admin-pagination');
            return;
        }
        if ((!info || info.parentElement === wrapper) && (!pager || pager.parentElement === wrapper)) {
            var footer = document.createElement('div');
            footer.className = 'admin-pagination';
            if (info) footer.appendChild(info);
            if (pager) footer.appendChild(pager);
            wrapper.appendChild(footer);
        }
    });

    $(document).on('preInit.dt.pianolitFields', function (event, settings) {
        var wrapper = settings.nTableWrapper;
        var template = document.getElementById(settings.nTable.id + '-filter-template');
        if (!wrapper || !template || wrapper.querySelector('[data-admin-filter-form]')) return;
        var form = template.content.firstElementChild.cloneNode(true);
        var $wrapper = $(wrapper);
        var api = new $.fn.dataTable.Api(settings);
        var initial = settings.oInit;
        var pageSizes = JSON.parse(template.dataset.pageSizes);
        var useSharedSizes = initial.lengthMenu === undefined && initial.aLengthMenu === undefined;
        if (useSharedSizes) settings.aLengthMenu = pageSizes;
        // A page may load a second legacy Bootstrap/DataTables build after the
        // main bundle. Establish the admin default before its first request too.
        if (!settings.oLoadedState && initial.pageLength === undefined && initial.iDisplayLength === undefined) {
            api.page.len(Number(template.dataset.pageSize));
        }
        function moveFeature(featureSelector, fieldName, inputSelector) {
            var $feature = $wrapper.find(featureSelector).first();
            var placeholder = form.querySelector('[name="' + fieldName + '"]');
            var field = placeholder.closest('.form-group');
            if (!$feature.length) { field.remove(); return null; }
            var $input = $feature.find(inputSelector).detach();
            var input = $input[0];
            input.id = placeholder.id;
            input.name = placeholder.name;
            $input.removeClass('form-control-sm custom-select custom-select-sm form-select').addClass('form-control');
            if (fieldName === 'search') {
                $input.addClass('pianolit-input').attr('placeholder', placeholder.placeholder).attr('maxlength', placeholder.maxLength);
            } else {
                if (useSharedSizes) $input.empty().append($(placeholder).children());
                if (!$input.find('option').filter(function () { return Number(this.value) === api.page.len(); }).length) {
                    $input.append(new Option(api.page.len(), api.page.len()));
                }
                input.value = api.page.len();
            }
            placeholder.replaceWith(input);
            $feature.empty().append($(field).contents()).attr('class', field.className + ' ' + featureSelector.slice(1));
            field.replaceWith($feature[0]);
            return input;
        }
        var search = moveFeature('.dataTables_filter', 'search', 'input');
        var length = moveFeature('.dataTables_length', 'per_page', 'select');
        if (!search && !length) return;
        // Retain vendor rows: the existing responsive table frame uses their
        // order for horizontal scrolling and loading placeholders.
        wrapper.prepend(form);
        form.addEventListener('submit', function (submitEvent) {
            submitEvent.preventDefault();
            if (search) api.search(search.value);
            if (length) api.page.len(Number(length.value));
            api.draw();
        });
    });
})(window.jQuery);
