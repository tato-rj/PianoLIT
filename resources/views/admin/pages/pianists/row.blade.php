<tr>
    <td class="admin-table__account">
        <div class="d-flex align-items-center gap-3">
            <img src="{{ storage($item->cover_path) }}" alt="" width="48" height="48" class="rounded-circle object-fit-cover flex-shrink-0" loading="lazy">
            <div>
                <a href="{{ route('resources.pianists.show', $item) }}" class="admin-table__primary" target="_blank" rel="noopener noreferrer">{{ $item->name }}</a>
                <small>{{ $item->alive_on ?: 'Life dates not recorded' }}</small>
            </div>
        </div>
    </td>
    <td>{{ optional($item->country)->nationality ?: 'Not recorded' }}</td>
    <td>
        @if($item->itunes_id)
            <span class="admin-table__badge admin-table__badge--green">Linked</span>
        @else
            <span class="admin-table__badge admin-table__badge--muted">Not linked</span>
        @endif
    </td>
    <td>
        <div class="d-flex justify-content-end gap-2">
            @include('admin.components.table-action', ['tableAction' => ['kind' => 'edit', 'url' => route('admin.pianists.edit', ['pianist' => $item] + $returnFilters), 'attributes' => ['aria-label' => 'Edit '.$item->name]]])
            @can('update', $item)
            @include('admin.components.table-action', ['tableAction' => ['kind' => 'delete', 'class' => 'delete', 'attributes' => ['data-url' => route('admin.pianists.destroy', ['pianist' => $item] + $returnFilters), 'data-bs-toggle' => 'modal', 'data-bs-target' => '#delete-modal', 'aria-label' => 'Delete '.$item->name]]])
            @endcan
        </div>
    </td>
</tr>
