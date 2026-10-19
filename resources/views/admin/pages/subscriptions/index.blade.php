@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper"><div class="container-fluid">
    @component('admin.components.page.title', [
        'icon' => 'mail', 'title' => 'Subscribers', 'subtitle' => 'Manage email subscriptions.',
        'action' => ['label' => 'Add new emails', 'modal' => 'add-modal'],
    ])
        @foreach(['members' => 'Export members', 'fans' => 'Export fans'] as $type => $label)
        <a class="btn btn-sm btn-light" href="{{ route('admin.subscriptions.export', ['type' => $type]) }}" target="_blank" rel="noopener noreferrer">@icon('file-text', ['mr' => 2]){{ $label }}</a>
        @endforeach
    @endcomponent

    @component('admin.components.table', [
        'id' => 'subscriptions-table', 'label' => 'Subscribers', 'items' => $subscriptions,
        'description' => number_format($subscriptions->total()).' matching subscribers.',
        'action' => route('admin.subscriptions.index'), 'state' => $filters,
        'search' => ['label' => 'Find a subscriber', 'placeholder' => 'Email, origin, form or subscriber ID'],
        'sorts' => \App\Services\Admin\SubscriptionsTable::SORTS,
        'columns' => [
            ['label' => 'Select', 'hidden' => true],
            ['label' => 'Date', 'sort' => ['asc' => 'date_asc', 'desc' => 'date_desc']],
            ['label' => 'ID', 'sort' => ['asc' => 'id_asc', 'desc' => 'id_desc']],
            ['label' => 'Email', 'sort' => ['asc' => 'email_asc', 'desc' => 'email_desc']],
            ['label' => 'Origin', 'sort' => ['asc' => 'origin_asc', 'desc' => 'origin_desc']],
            ['label' => 'Form ID', 'sort' => ['asc' => 'form_asc', 'desc' => 'form_desc']],
            ['label' => 'Actions', 'hidden' => true],
        ],
        'emptyTitle' => $filters['search'] ? 'No matching subscribers' : 'No subscribers yet.',
        'emptyDescription' => $filters['search'] ? 'Try another email, origin, form or subscriber ID.' : 'Email subscribers will appear here when they sign up.',
    ])
        @slot('toolbar')
            @if($subscriptions->count())
                @include('admin.components.table-bulk-delete', ['id' => 'subscriptions-selection', 'tableId' => 'subscriptions-table', 'action' => route('admin.subscriptions.destroy-many', $returnFilters), 'label' => 'subscribers'])
            @endif
        @endslot
        @include('admin.pages.subscriptions.rows')
    @endcomponent
</div></div>
@include('admin.pages.subscriptions.create')
@include('admin.components.modals.delete')
@endsection

@section('scripts')
    <script src="{{ asset('js/components/admin-table-bulk-delete.js') }}" defer></script>
@endsection
