@extends('admin.layouts.app')

@section('head')
<link rel="preload" href="{{ asset('css/vendor/flag-icon/flag-icon.min.css') }}" as="style">
<link href="{{ asset('css/vendor/flag-icon/flag-icon.min.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="content-wrapper"><div class="container-fluid">
    @include('admin.components.page.title', ['icon' => 'users', 'title' => 'Users', 'subtitle' => 'Use this page to view and manage the profile of our users.'])
    @component('admin.components.table', [
        'id' => 'users-table', 'label' => 'Users', 'items' => $users,
        'description' => number_format($users->total()).' matching accounts.',
        'action' => route('admin.users.index'), 'state' => $filters,
        'search' => ['label' => 'Find an account', 'placeholder' => 'Name, email, ID, country, region or city'],
        'sorts' => \App\Services\Admin\UsersTable::SORTS,
        'columns' => [
            ['label' => 'Date', 'sort' => ['asc' => 'date_asc', 'desc' => 'date_desc']],
            ['label' => 'ID', 'sort' => ['asc' => 'id_asc', 'desc' => 'id_desc']],
            ['label' => 'Account', 'sort' => ['asc' => 'name_asc', 'desc' => 'name_desc']],
            ['label' => 'Origin', 'sort' => ['asc' => 'origin_asc', 'desc' => 'origin_desc']],
            'Status', ['label' => 'Actions', 'hidden' => true],
        ],
        'tableClass' => 'admin-users-table',
        'emptyTitle' => $filters['search'] ? 'No matching users' : 'No users yet.',
        'emptyDescription' => $filters['search'] ? 'Try another name, email or user ID.' : 'Accounts will appear here when people sign up.',
    ])
        @include('admin.pages.users.table.rows')
    @endcomponent
</div></div>
@endsection
