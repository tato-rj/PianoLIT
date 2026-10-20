@foreach($pieces as $item)
<tr>
    <td>{{ $item->id }}</td>
    <td class="admin-table__account">
        <a class="admin-table__primary" href="{{ route('webapp.pieces.show', $item) }}" target="_blank" rel="noopener noreferrer">{{ $item->long_name }}</a>
        @if(!$item->hasAudio())
        <small><a href="{{ youtube($item->long_name.' by '.optional($item->composer)->name) }}" target="_blank" rel="noopener noreferrer">Find a recording @icon('external-link', ['mr' => 0, 'classes' => 'icon-size-xs'])</a></small>
        @endif
    </td>
    <td class="text-nowrap">{{ optional($item->composer)->name ?? 'Unknown composer' }}</td>
    <td>
        <button type="button" class="btn btn-sm btn-light text-nowrap" id="piece-tags-{{ $item->id }}" data-bs-toggle="modal" data-bs-target="#piece-quick-edit" data-piece-edit-url="{{ route('admin.pieces.load-tags', $item) }}" data-piece-edit-title="Tags · {{ $item->long_name }}" aria-label="Edit tags for {{ $item->long_name }}"><span data-piece-tags-count>{{ max(0, $item->tags_count) }}</span> @icon('pencil', ['mr' => 0, 'classes' => 'icon-size-xs'])</button>
    </td>
    <td>
        <button type="button" class="btn btn-sm btn-light text-nowrap" id="piece-level-{{ $item->id }}" data-bs-toggle="modal" data-bs-target="#piece-quick-edit" data-piece-edit-url="{{ route('admin.pieces.load-levels', $item) }}" data-piece-edit-title="Level · {{ $item->long_name }}" aria-label="Edit level for {{ $item->long_name }}"><span data-piece-level-label>{{ ucfirst(optional($item->extendedLevel)->name ?? 'Not set') }}</span> @icon('pencil', ['mr' => 0, 'classes' => 'icon-size-xs'])</button>
    </td>
    <td class="text-nowrap">
        @if($item->is_free)<span class="admin-table__badge admin-table__badge--green">Current Freepick</span>@endif
        @if($item->highlighted_at)<time class="d-block" datetime="{{ $item->highlighted_at->toIso8601String() }}">{{ $item->highlighted_at->toFormattedDateString() }}</time>@else<span class="text-muted">Never highlighted</span>@endif
    </td>
    <td>{{ number_format($item->favorites_count) }}</td>
    <td>
        <div class="d-flex flex-nowrap justify-content-end align-items-center gap-1">
            <a href="{{ route('admin.pieces.edit', $item) }}" class="btn btn-sm btn-light" aria-label="Edit {{ $item->long_name }}">Edit</a>
            @if(!$item->is_free && $item->hasImage())
            <form method="POST" action="{{ route('admin.pieces.highlight', $item) }}" class="m-0">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn-raw text-muted p-2" title="Make this the Freepick" aria-label="Make {{ $item->long_name }} the Freepick">@icon('award', ['mr' => 0])</button>
            </form>
            @endif
            <form method="POST" action="{{ route('admin.pieces.hijack', $item) }}" class="m-0">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn-raw text-muted p-2" title="Change creation date to today" aria-label="Change creation date to today for {{ $item->long_name }}">@icon('calendar-days', ['mr' => 0])</button>
            </form>
            <button type="button" class="delete btn-raw text-muted p-2" data-url="{{ route('admin.pieces.destroy', ['piece' => $item] + $returnFilters) }}" data-bs-toggle="modal" data-bs-target="#delete-modal" title="Delete piece" aria-label="Delete {{ $item->long_name }}">@icon('trash-2', ['mr' => 0])</button>
        </div>
    </td>
</tr>
@endforeach
