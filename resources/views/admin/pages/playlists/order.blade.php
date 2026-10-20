@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper"><div class="container-fluid">
    @include('admin.components.page.title', [
        'title' => 'Arrange playlists',
        'breadcrumbLabel' => 'Arrange order',
        'breadcrumbs' => [['label' => 'Playlists', 'url' => route('admin.playlists.index')]],
        'subtitle' => 'The first playlist becomes the featured collection.',
    ])
    @if($errors->any() || $errors->getBag('playlistOrder')->any())
        <div class="alert alert-red" role="alert">{{ $errors->getBag('playlistOrder')->first() ?: $errors->first() }}</div>
    @endif
    <form method="POST" action="{{ route('admin.playlists.reorder') }}" data-playlist-order>
        @csrf
        @method('PATCH')
        <input type="hidden" name="group" value="general">
        @foreach($playlists as $playlist)<input type="hidden" name="original_ids[]" value="{{ $playlist->id }}">@endforeach
        @component('admin.components.table', [
            'id' => 'playlist-order-table', 'label' => 'Playlist display order', 'items' => $playlists,
            'description' => 'Drag a handle or use the arrow buttons, then save your changes. All playlists appear here.',
            'columns' => [['label' => 'Drag', 'hidden' => true], 'Position', 'Playlist', ['label' => 'Move', 'hidden' => true]],
            'emptyTitle' => 'No playlists to arrange', 'emptyDescription' => 'Create a playlist to get started.',
        ])
            @foreach($playlists as $playlist)
            <tr data-playlist-id="{{ $playlist->id }}">
                <td><span class="sort-handle text-muted" aria-hidden="true">@icon('grip-vertical', ['mr' => 0])</span><input type="hidden" name="ids[]" value="{{ $playlist->id }}"></td>
                <td data-playlist-position>{{ $loop->iteration }}</td>
                <td>@include('admin.pages.playlists.table.label', ['link' => false])</td>
                <td><div class="d-flex flex-nowrap justify-content-end gap-2">
                    @include('admin.components.table-action', ['tableAction' => ['label' => 'Move '.$playlist->name.' up', 'icon' => 'arrow-up', 'iconOnly' => true, 'attributes' => ['data-playlist-move' => 'up', 'disabled' => $loop->first]]])
                    @include('admin.components.table-action', ['tableAction' => ['label' => 'Move '.$playlist->name.' down', 'icon' => 'arrow-down', 'iconOnly' => true, 'attributes' => ['data-playlist-move' => 'down', 'disabled' => $loop->last]]])
                </div></td>
            </tr>
            @endforeach
        @endcomponent
        @if($playlists->isNotEmpty())
            @component('admin.components.table-save-actions', ['status' => 'All changes saved'])
                <button type="submit" class="btn btn-primary" data-playlist-save>@icon('check')Save changes</button>
            @endcomponent
        @endif
    </form>
</div></div>
@endsection

@section('scripts')
<script src="{{ mix('js/views/admin-playlists.js') }}"></script>
@endsection
