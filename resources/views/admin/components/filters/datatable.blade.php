@php
    $legacyFilterId = $tableId.'-filters';
    $legacyPageSizes = \App\Support\AdminPagination::PAGE_SIZES;
@endphp
<template id="{{ $tableId }}-filter-template" data-admin-table-filter-template data-page-size="{{ \App\Support\AdminPagination::PER_PAGE }}" data-page-sizes="{{ json_encode($legacyPageSizes) }}">
    @include('admin.components.filters.form', [
        'id' => $legacyFilterId, 'label' => 'Table filters', 'action' => request()->url(), 'preview' => false, 'hidden' => [], 'slot' => null, 'note' => null, 'resetUrl' => null, 'state' => [],
        'fields' => [
            ['type' => 'search', 'name' => 'search', 'label' => 'Search', 'placeholder' => 'Search this table'],
            ['name' => 'per_page', 'label' => 'Per page', 'options' => array_combine($legacyPageSizes, $legacyPageSizes), 'value' => \App\Support\AdminPagination::PER_PAGE],
        ],
        'buttons' => [['label' => 'Apply filters']],
    ])
</template>
