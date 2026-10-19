@once
    @push('admin-table-assets')
    <script src="{{ mix('js/components/admin-table-loading.js') }}"></script>
    @endpush
@endonce
<div class="admin-datatable" data-admin-datatable>
    <div class="admin-datatable__placeholder admin-datatable__placeholder--top dataTables_wrapper" aria-hidden="true">
        @include('admin.components.filters.form', ['id' => $tableId.'-placeholder-filters', 'label' => 'Loading table filters', 'preview' => true, 'slot' => null, 'note' => null, 'resetUrl' => null, 'state' => [], 'fields' => [
            ['type' => 'search', 'name' => 'search', 'label' => 'Search', 'disabled' => true, 'class' => 'dataTables_filter'],
            ['name' => 'per_page', 'label' => 'Per page', 'options' => [\App\Support\AdminPagination::PER_PAGE => \App\Support\AdminPagination::PER_PAGE], 'disabled' => true, 'class' => 'dataTables_length'],
        ], 'buttons' => [['label' => 'Apply filters', 'disabled' => true]]])
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
