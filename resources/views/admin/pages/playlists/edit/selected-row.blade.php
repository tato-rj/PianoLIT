<tr data-selected-piece="{{ $piece->id }}" data-piece-name="{{ $piece->short_name }}">
    <td><span class="sort-handle text-muted" aria-hidden="true">@icon('grip-vertical', ['mr' => 0])</span><input type="hidden" name="pieces[]" value="{{ $piece->id }}"></td>
    <td data-piece-position>{{ $position ?? '' }}</td>
    <td>@include('admin.pages.playlists.edit.piece-label')</td>
    <td class="text-nowrap">@include('webapp.components.piece.level-indicator')</td>
    <td><div class="d-flex flex-nowrap justify-content-end gap-2">
        @include('admin.components.table-action', ['tableAction' => ['label' => 'Move '.$piece->short_name.' up', 'icon' => 'arrow-up', 'iconOnly' => true, 'attributes' => ['data-piece-move' => 'up', 'disabled' => $first ?? false]]])
        @include('admin.components.table-action', ['tableAction' => ['label' => 'Move '.$piece->short_name.' down', 'icon' => 'arrow-down', 'iconOnly' => true, 'attributes' => ['data-piece-move' => 'down', 'disabled' => $last ?? false]]])
        @include('admin.pages.playlists.edit.preview-actions')
        @include('admin.components.table-action', ['tableAction' => ['kind' => 'delete', 'label' => 'Remove '.$piece->short_name.' from playlist', 'attributes' => ['data-piece-remove' => true]]])
    </div></td>
</tr>
