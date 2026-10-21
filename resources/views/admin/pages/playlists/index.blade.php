@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper"><div class="container-fluid">
    @component('admin.components.page.title', [
        'title' => 'Playlists', 'subtitle' => 'Curate collections, manage publication and arrange their display order.',
    ])
        <button type="button" class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#add-modal">@icon('plus')Create a new playlist</button>
        <button type="button" class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#playlist-tags-modal">@icon('tag')Manage tags</button>
    @endcomponent
    @component('admin.components.table', [
        'id' => 'playlists-table', 'label' => 'Playlists', 'items' => $playlists,
        'description' => number_format($playlists->total()).' matching playlists.',
        'action' => route('admin.playlists.index'), 'state' => $filters,
        'search' => ['label' => 'Find a playlist', 'placeholder' => 'Name, subtitle or featured tag'],
        'filters' => [
            ['name' => 'status', 'label' => 'Publication', 'options' => ['' => 'All statuses', 'published' => 'Published', 'draft' => 'Draft', 'scheduled' => 'Scheduled']],
        ],
        'sorts' => \App\Services\Admin\PlaylistsTable::SORTS,
        'showReset' => $filters['search'] !== '' || $filters['status'] !== '' || $filters['sort'] !== 'order_asc' || $filters['per_page'] !== \App\Support\AdminPagination::PER_PAGE,
        'resetUrl' => route('admin.playlists.index'),
        'columns' => [
            ['label' => 'Order', 'sort' => ['asc' => 'order_asc', 'desc' => 'order_desc']],
            ['label' => 'Playlist', 'sort' => ['asc' => 'name_asc', 'desc' => 'name_desc']],
            'Publication',
            ['label' => 'Pieces', 'sort' => ['asc' => 'pieces_asc', 'desc' => 'pieces_desc']],
            ['label' => 'Actions', 'hidden' => true],
        ],
        'tableClass' => 'admin-playlists-table',
        'emptyTitle' => $playlists->total() ? 'No playlists on this page' : 'No matching playlists',
        'emptyDescription' => 'Clear your search or choose another publication status.',
        'emptyAction' => ['label' => 'Show all playlists', 'url' => route('admin.playlists.index')],
    ])
        @slot('toolbar')
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                <p class="small text-muted mb-0">The first playlist is the featured collection.</p>
                <a href="{{ route('admin.playlists.order') }}" class="btn btn-dark btn-sm">@icon('arrow-down-up')Arrange order</a>
            </div>
        @endslot
        @include('admin.pages.playlists.table.rows')
    @endcomponent
</div></div>
@include('admin.components.modals.delete')
@include('admin.pages.playlists.create')
@include('admin.pages.playlists.tags.modal')
@endsection

@section('scripts')
<script src="{{ mix('js/views/admin-playlists.js') }}"></script>
<script src="{{ mix('js/views/admin-playlist-tags.js') }}"></script>
@endsection
