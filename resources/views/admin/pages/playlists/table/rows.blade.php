@foreach($playlists as $playlist)
<tr>
    <td class="text-nowrap">
        {{ $playlist->order + 1 }}
    </td>
    <td>@include('admin.pages.playlists.table.label')</td>
    <td class="text-nowrap">
        @if(!$playlist->published_at)
            <span class="admin-table__badge admin-table__badge--muted">Draft</span>
        @elseif($playlist->published_at->isFuture())
            <span class="admin-table__badge admin-table__badge--warning">Scheduled</span>
            <small>{{ $playlist->published_at->format('M j, Y') }}</small>
        @else
            <span class="admin-table__badge admin-table__badge--green">Published</span>
            <small>{{ $playlist->published_at->toFormattedDateString() }}</small>
        @endif
    </td>
    <td class="text-nowrap">{{ number_format($playlist->pieces_count) }}<small>{{ number_format($playlist->playable_pieces_count) }} playable</small></td>
    <td>
        <div class="d-flex flex-nowrap align-items-center justify-content-end gap-2">
            @include('admin.components.table-action', ['tableAction' => ['kind' => 'edit', 'url' => route('admin.playlists.edit', $playlist), 'attributes' => ['aria-label' => 'Edit '.$playlist->name]]])
            <form method="POST" action="{{ route('admin.playlists.publication', ['playlist' => $playlist, 'return_to' => 'directory'] + $returnFilters) }}" class="m-0">
                @csrf
                @method('PATCH')
                @include('admin.components.table-action', ['tableAction' => ['type' => 'submit', 'label' => $playlist->published_at ? 'Unpublish' : 'Publish', 'icon' => $playlist->published_at ? 'archive' : 'eye', 'attributes' => ['aria-label' => ($playlist->published_at ? 'Unpublish ' : 'Publish ').$playlist->name]]])
            </form>
            @include('admin.components.table-action', ['tableAction' => ['kind' => 'delete', 'class' => 'delete', 'attributes' => ['data-url' => route('admin.playlists.destroy', ['playlist' => $playlist, 'return_to' => 'directory'] + $returnFilters), 'data-bs-toggle' => 'modal', 'data-bs-target' => '#delete-modal', 'aria-label' => 'Delete '.$playlist->name]]])
        </div>
    </td>
</tr>
@endforeach
