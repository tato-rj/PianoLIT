@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper"><div class="container-fluid">
    @include('admin.components.page.title', [
        'title' => 'Pianists',
        'subtitle' => 'Manage pianist profiles, portraits and Apple Music albums.',
        'action' => ['label' => 'Add a pianist', 'modal' => 'add-modal'],
    ])
    @component('admin.components.table', [
        'id' => 'pianists-table', 'label' => 'Pianists', 'items' => $pianists,
        'action' => route('admin.pianists.index'), 'state' => $filters,
        'search' => ['label' => 'Find a pianist', 'placeholder' => 'Name or nationality'],
        'filters' => [['name' => 'country_id', 'label' => 'Nationality', 'options' => ['' => 'All nationalities'] + $countries->pluck('nationality', 'id')->all()]],
        'sorts' => \App\Services\Admin\PianistsTable::SORTS,
        'showReset' => $filters['search'] !== '' || !empty($filters['country_id']) || $filters['sort'] !== 'name_asc' || $filters['per_page'] !== \App\Support\AdminPagination::PER_PAGE,
        'resetUrl' => route('admin.pianists.index'),
        'columns' => [
            ['label' => 'Pianist', 'sort' => ['asc' => 'name_asc', 'desc' => 'name_desc']],
            'Nationality', 'Apple Music', ['label' => 'Actions', 'hidden' => true],
        ],
        'tableClass' => 'admin-pianists-table',
        'emptyTitle' => $pianists->total() ? 'No pianists on this page' : 'No matching pianists',
        'emptyDescription' => 'Try another name or nationality, or add a pianist to get started.',
        'emptyAction' => ['label' => 'Show all pianists', 'url' => route('admin.pianists.index')],
    ])
        @foreach($pianists as $item)
            @include('admin.pages.pianists.row')
        @endforeach
    @endcomponent
</div></div>
@include('admin.components.modals.delete')
@include('admin.pages.pianists.create')
@endsection

@section('scripts')
<script src="{{ mix('js/views/admin-pianists.js') }}"></script>
@endsection
