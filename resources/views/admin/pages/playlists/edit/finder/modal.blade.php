@component('components.modal', ['id' => 'playlist-piece-finder', 'header' => 'Add pieces', 'options' => ['size' => 'xl']])
@slot('body')
    <div data-playlist-finder data-url="{{ route('admin.playlists.pieces', $playlist) }}">
        <p class="text-muted">Search the repertoire and add pieces to your playlist. Your selection stays here while you browse.</p>
        <div class="alert alert-red" role="alert" data-finder-error hidden></div>
        <p class="small text-muted" role="status" aria-live="polite" data-finder-status></p>
        <div data-finder-results>@include('admin.pages.playlists.edit.finder.table')</div>
    </div>
@endslot
@slot('footer')
    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">@icon('check')Done</button>
@endslot
@endcomponent
