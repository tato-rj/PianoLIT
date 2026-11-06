@extends('admin.layouts.app')

@section('content')
@php
    $label = $kind === 'ebooks' ? 'eBooks' : 'eScores';
    $singular = $kind === 'ebooks' ? 'eBook' : 'eScore';
    $filtered = $filters['search'] !== '' || !empty($filters['status']);
@endphp
<div class="content-wrapper"><div class="container-fluid">
    @component('admin.components.page.title', [
        'title' => $label, 'subtitle' => 'Manage products, purchases and publication.',
        'action' => ['label' => 'Create a new '.$singular, 'url' => route('admin.'.$kind.'.create')],
    ])
        <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#digital-topics-modal">@icon('tag')Manage topics</button>
    @endcomponent
    @component('admin.components.table', [
        'id' => $kind.'-table', 'label' => $label, 'items' => $products,
        'action' => route('admin.'.$kind.'.index'), 'state' => $filters,
        'search' => ['label' => 'Find an '.$singular, 'placeholder' => 'Title, '.($kind === 'ebooks' ? 'author' : 'composer').', description or ID'],
        'filters' => [['name' => 'status', 'label' => 'Publication', 'options' => ['' => 'All statuses', 'published' => 'Published', 'draft' => 'Draft']]],
        'sorts' => \App\Services\Admin\DigitalProductsTable::SORTS,
        'showReset' => $filtered || $filters['sort'] !== 'date_desc' || $filters['per_page'] !== \App\Support\AdminPagination::PER_PAGE,
        'columns' => [
            ['label' => 'Date', 'sort' => ['asc' => 'date_asc', 'desc' => 'date_desc']],
            ['label' => $singular, 'sort' => ['asc' => 'title_asc', 'desc' => 'title_desc']],
            ['label' => 'Purchases', 'sort' => ['asc' => 'purchases_asc', 'desc' => 'purchases_desc']],
            ['label' => 'Publication', 'sort' => ['asc' => 'published_asc', 'desc' => 'published_desc']],
            ['label' => 'Actions', 'hidden' => true],
        ],
        'emptyTitle' => $products->total() ? 'No '.$label.' on this page' : ($filtered ? 'No matching '.$label : 'No '.$label.' yet'),
        'emptyDescription' => $filtered ? 'Try another search or publication status.' : 'Create an '.$singular.' to add it to the shop.',
        'emptyAction' => $products->total() ? ['label' => 'First page', 'url' => $products->url(1)] : null,
    ])
        @include('admin.pages.digital.table.rows')
    @endcomponent
</div></div>
@include('admin.components.modals.delete')
@include('admin.pages.digital.topics.modal')
@if($purchaseProduct)@include('admin.pages.digital.table.purchases')@endif
@endsection

@section('scripts')
<script>
(function () {
    const modal = document.getElementById('digital-topics-modal');
    modal.addEventListener('hidden.bs.modal', function () {
        const url = new URL(window.location.href);
        if (!url.searchParams.has('topics')) return;
        url.searchParams.delete('topics');
        window.location.assign(url);
    });
    @if(request()->query('topics') === '1' && !$purchaseProduct)
    window.bootstrap.Modal.getOrCreateInstance(modal).show();
    @endif
})();
</script>
@if($purchaseProduct)
<script>
(function () {
    const modal = document.getElementById('digital-purchases-modal');
    modal.addEventListener('hidden.bs.modal', function () {
        window.location.assign(@json(route('admin.'.$kind.'.index', $returnFilters)));
    });
    window.bootstrap.Modal.getOrCreateInstance(modal).show();
})();
</script>
@endif
@endsection
