@component('admin.components.editor.section', ['id' => 'piece-options', 'title' => 'Options', 'class' => 'mb-4', 'description' => 'Piece #'.$piece->id.' · '.number_format($piece->views_count).' '.str_plural('view', $piece->views_count).($piece->created_at ? ' · Created '.$piece->created_at->toFormattedDateString() : '')])
    <div class="admin-editor-options">
        <a class="btn btn-sm btn-dark" href="{{ route('webapp.pieces.show', $piece) }}" target="_blank" rel="noopener noreferrer">@icon('external-link')View piece</a>
        @if($piece->is_free)
            <button type="button" class="btn btn-sm btn-dark" disabled>@icon('award')Current Freepick</button>
        @elseif($piece->hasImage())
            <form method="POST" action="{{ route('admin.pieces.highlight', $piece) }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-sm btn-dark">@icon('award')Make this the Freepick</button>
            </form>
        @else
            <button type="button" class="btn btn-sm btn-dark" disabled title="Save a cover image before making this the Freepick">@icon('award')Make this the Freepick</button>
        @endif
        <form method="POST" action="{{ route('admin.pieces.hijack', $piece) }}">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn btn-sm btn-dark">@icon('calendar-days')Set creation date to today</button>
        </form>
        <button type="button" class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#abrsm-modal">ABRSM levels</button>
        <button type="button" class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#rcm-modal">RCM levels</button>
    </div>
@endcomponent
