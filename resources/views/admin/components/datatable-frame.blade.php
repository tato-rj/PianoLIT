@once
    @push('admin-table-assets')
    <script src="{{ mix('js/components/admin-table-loading.js') }}"></script>
    @endpush
@endonce
<div class="admin-datatable" data-admin-datatable>
    <div class="admin-datatable__placeholder admin-datatable__placeholder--top dataTables_wrapper" aria-hidden="true">
        <div class="row">
            <div class="col-sm-12 col-md-6"><div class="dataTables_length"><label>Show <select class="form-select" disabled tabindex="-1"><option>{{ \App\Support\AdminPagination::PER_PAGE }}</option></select> entries</label></div></div>
            <div class="col-sm-12 col-md-6"><div class="dataTables_filter"><label>Search:<input type="search" class="form-control" disabled tabindex="-1"></label></div></div>
        </div>
    </div>
    {{ $slot }}
    <div class="admin-datatable__placeholder admin-datatable__placeholder--bottom dataTables_wrapper" aria-hidden="true">
        <div class="row">
            <div class="col-sm-12 col-md-5"><div class="dataTables_info">Showing entries</div></div>
            <div class="col-sm-12 col-md-7"><div class="dataTables_paginate"><ul class="pagination"><li class="page-item"><span class="page-link">←</span></li><li class="page-item"><span class="page-link">1</span></li><li class="page-item"><span class="page-link">2</span></li><li class="page-item"><span class="page-link">3</span></li><li class="page-item"><span class="page-link">→</span></li></ul></div></div>
        </div>
    </div>
    <span class="visually-hidden" role="status" data-admin-table-status hidden>Loading table…</span>
</div>
<script>
if (window.AdminTableLoading) window.AdminTableLoading.prepare(document.getElementById(@json($tableId)));
</script>
