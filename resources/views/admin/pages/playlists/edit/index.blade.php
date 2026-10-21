@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper"><div class="container-fluid" data-playlist-editor-root>
    @include('admin.components.page.title', [
        'title' => 'Edit '.$playlist->name, 'subtitle' => 'Shape the collection, choose its pieces and arrange their order.',
        'breadcrumbs' => [['label' => 'Playlists', 'url' => route('admin.playlists.index')]],
    ])
    @if($errors->any())<div class="alert alert-red" role="alert">{{ $errors->first() }} Your other changes have been kept. Choose the cover file again if needed.</div>@endif

    <form id="playlist-edit-form" method="POST" action="{{ route('admin.playlists.update', $playlist) }}" enctype="multipart/form-data" data-playlist-editor data-restored="{{ $restored ? 'true' : 'false' }}">
        @csrf
        @method('PATCH')
        <input type="hidden" name="_playlist_editor" value="{{ $playlist->id }}">
        @include('admin.pages.playlists.edit.form')
        @include('admin.pages.playlists.edit.selected')
        @component('admin.components.table-save-actions', ['status' => $restored ? 'Unsaved changes' : 'All changes saved'])
            <button type="submit" class="btn btn-primary" data-playlist-save>@icon('check')Save changes</button>
        @endcomponent
    </form>
    <template data-playlist-empty>@include('admin.components.table-empty-row', ['columns' => range(1, 5), 'emptyTitle' => 'No pieces yet', 'emptyDescription' => 'Use Add pieces to start building this playlist.'])</template>
    @include('admin.pages.playlists.edit.finder.modal')
</div></div>
@endsection

@section('scripts')
<script src="{{ mix('js/views/admin-playlist-editor.js') }}"></script>
@endsection
