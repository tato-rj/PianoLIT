@component('components.draggable.cards.small', ['model' => $playlist])
<span class="badge alert-{{ $playlist->is_featured ? 'green' : 'grey' }} align-text-bottom rounded-pill">{{ $playlist->order + 1 }}</span>
<span>{{ $playlist->name }} <small class="text-muted">&middot; {{ $playlist->pieces_count }} pieces</small></span>
@slot('controls')
    <div class="d-flex flex-nowrap align-items-center gap-2">
        @include('admin.components.table-action', ['tableAction' => ['kind' => 'edit', 'url' => route('admin.playlists.edit', $playlist)]])
        @include('admin.components.table-action', ['tableAction' => ['kind' => 'delete', 'class' => 'delete', 'attributes' => ['data-url' => route('admin.playlists.destroy', $playlist->id), 'data-bs-toggle' => 'modal', 'data-bs-target' => '#delete-modal']]])
        <form method="POST" action="{{ route('admin.playlists.publication', $playlist) }}" class="m-0">
            @csrf
            @method('PATCH')
            @include('admin.components.table-action', ['tableAction' => ['type' => 'submit', 'label' => $playlist->published_at ? 'Unpublish' : 'Publish']])
        </form>
    </div>
@endslot
@endcomponent
