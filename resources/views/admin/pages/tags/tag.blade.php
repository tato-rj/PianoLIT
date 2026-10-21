<tr>
    <td><span class="admin-table__primary">{{ $tag->name }}</span></td>
    <td>{{ ucfirst($tag->type) }}</td>
    <td>{{ number_format($tag->pieces_count) }}</td>
    <td>@if($tag->ordering !== null){{ $tag->ordering }}@else<span class="text-muted">Not set</span>@endif</td>
    <td>
        <div class="d-flex align-items-center justify-content-end gap-2">
            @include('admin.components.table-action', ['tableAction' => ['kind' => 'edit', 'attributes' => [
                'aria-label' => 'Edit tag '.$tag->name,
                'data-bs-toggle' => 'modal', 'data-bs-target' => '#tag-modal',
                'data-tag-edit' => true, 'data-name' => $tag->name, 'data-type' => $tag->type,
                'data-ordering' => $tag->ordering, 'data-creator' => optional($tag->creator)->name ?? 'Not recorded',
                'data-edit-url' => route('admin.tags.update', ['tag' => $tag->id] + $returnFilters),
            ]]])
            @include('admin.components.table-action', ['tableAction' => ['kind' => 'delete', 'attributes' => [
                'aria-label' => 'Delete tag '.$tag->name,
                'data-bs-toggle' => 'modal', 'data-bs-target' => '#delete-tag-modal',
                'data-name' => $tag->name, 'data-pieces' => $tag->pieces_count,
                'data-delete-url' => route('admin.tags.destroy', ['tag' => $tag->id] + $returnFilters),
            ]]])
        </div>
    </td>
</tr>
