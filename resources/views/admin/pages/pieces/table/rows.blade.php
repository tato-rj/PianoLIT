@foreach($pieces as $item)
<tr>
    <td>{{ $item->id }}</td>
    <td class="admin-table__account">
        <a class="admin-table__primary clamp-2" title="{{ $item->long_name }}" href="{{ route('webapp.pieces.show', $item) }}" target="_blank" rel="noopener noreferrer">{{ $item->long_name }}</a>
        @if(!$item->hasAudio())
        <small><a href="{{ youtube($item->long_name.' by '.optional($item->composer)->name) }}" target="_blank" rel="noopener noreferrer">Find a recording @icon('external-link', ['mr' => 0, 'classes' => 'icon-size-xs'])</a></small>
        @endif
    </td>
    <td class="text-nowrap">{{ optional($item->composer)->name ?? 'Unknown composer' }}</td>
    <td class="text-nowrap">{{ ucfirst(optional($item->extendedLevel)->name ?? 'Not set') }}</td>
    <td class="text-nowrap">
        @if($item->is_free)
            <span class="admin-table__badge admin-table__badge--green">Current Freepick</span>
        @elseif($item->highlighted_at)
            <time class="d-block" datetime="{{ $item->highlighted_at->toIso8601String() }}">{{ $item->highlighted_at->toFormattedDateString() }}</time>
        @else
            <span class="text-muted">Never highlighted</span>
        @endif
    </td>
    <td>{{ number_format($item->favorites_count) }}</td>
    <td>
        <div class="d-flex flex-nowrap justify-content-end align-items-center gap-2">
            @include('admin.components.table-action', ['tableAction' => ['kind' => 'edit', 'url' => route('admin.pieces.edit', $item), 'attributes' => ['aria-label' => 'Edit '.$item->long_name]]])
            @include('admin.components.table-action', ['tableAction' => ['kind' => 'delete', 'class' => 'delete', 'attributes' => ['data-url' => route('admin.pieces.destroy', ['piece' => $item] + $returnFilters), 'data-bs-toggle' => 'modal', 'data-bs-target' => '#delete-modal', 'title' => 'Delete piece', 'aria-label' => 'Delete '.$item->long_name]]])
            @if(!$item->is_free && $item->hasImage())
            <form method="POST" action="{{ route('admin.pieces.highlight', $item) }}" class="m-0">
                @csrf
                @method('PATCH')
                @include('admin.components.table-action', ['tableAction' => ['type' => 'submit', 'label' => 'Make this the Freepick', 'icon' => 'award', 'iconOnly' => true, 'attributes' => ['aria-label' => 'Make '.$item->long_name.' the Freepick']]])
            </form>
            @endif
            <form method="POST" action="{{ route('admin.pieces.hijack', $item) }}" class="m-0">
                @csrf
                @method('PATCH')
                @include('admin.components.table-action', ['tableAction' => ['type' => 'submit', 'label' => 'Change creation date to today', 'icon' => 'calendar-days', 'iconOnly' => true, 'attributes' => ['aria-label' => 'Change creation date to today for '.$item->long_name]]])
            </form>
        </div>
    </td>
</tr>
@endforeach
