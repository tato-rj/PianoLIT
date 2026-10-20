@foreach($pieces as $item)
<tr>
    <td>{{ $item->id }}</td>
    <td class="admin-table__account">
        <a class="admin-table__primary clamp-2" title="{{ $item->long_name }}" href="{{ route('webapp.pieces.show', $item) }}" target="_blank" rel="noopener noreferrer">{{ $item->long_name }}</a>
        @if(!$item->hasAudio())
        <small><a href="{{ youtube($item->long_name.' by '.optional($item->composer)->name) }}" target="_blank" rel="noopener noreferrer">Find a recording @icon('external-link', ['mr' => 0, 'classes' => 'icon-size-xs'])</a></small>
        @endif
    </td>
    <td class="text-nowrap">@include('components.composer-label', ['composer' => $item->composer])</td>
    <td class="text-nowrap">
        <span class="d-inline-flex align-items-center">
            @if($item->level_name)
                @icon('circle', ['mr' => 2, 'classes' => 'color-'.$item->level_name, 'filled' => true])
            @endif
            <span>{{ ucfirst(optional($item->extendedLevel)->name ?? 'Not set') }}</span>
        </span>
    </td>
    <td class="text-nowrap">
        @if($item->is_free)
            <span class="admin-table__badge admin-table__badge--green">Current Freepick</span>
        @elseif($item->highlighted_at)
            <time class="d-block" datetime="{{ $item->highlighted_at->toIso8601String() }}">@icon('calendar-check', ['color' => 'green']){{ $item->highlighted_at->toFormattedDateString() }}</time>
        @endif
        @include('admin.pages.pieces.table.scheduled-date')
    </td>
    <td>{{ number_format($item->favorites_count) }}</td>
    <td>
        <div class="d-flex flex-nowrap justify-content-end align-items-center gap-2">
            @include('admin.components.table-action', ['tableAction' => ['kind' => 'edit', 'url' => route('admin.pieces.edit', $item), 'attributes' => ['aria-label' => 'Edit '.$item->long_name]]])
            @include('admin.pages.pieces.table.schedule-action')
            @include('admin.components.table-action', ['tableAction' => ['kind' => 'delete', 'class' => 'delete', 'attributes' => ['data-url' => route('admin.pieces.destroy', ['piece' => $item] + $returnFilters), 'data-bs-toggle' => 'modal', 'data-bs-target' => '#delete-modal', 'title' => 'Delete piece', 'aria-label' => 'Delete '.$item->long_name]]])
        </div>
    </td>
</tr>
@endforeach
