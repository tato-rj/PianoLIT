@component('admin.components.editor.section', ['id' => 'piece-options', 'title' => 'Options', 'class' => 'mb-4', 'description' => 'Piece #'.$piece->id.' · '.number_format($piece->views_count).' '.str_plural('view', $piece->views_count).($piece->created_at ? ' · Created '.$piece->created_at->toFormattedDateString() : '')])
    <div class="admin-editor-options">
        <a class="btn btn-sm btn-dark" href="{{ route('webapp.pieces.show', $piece) }}" target="_blank" rel="noopener noreferrer">@icon('external-link')View piece</a>
        <form method="POST" action="{{ route('admin.pieces.hijack', $piece) }}" onsubmit="return window.confirm('Set the creation date of this piece to today?');">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn btn-sm btn-dark">@icon('calendar-days')Set creation date to today</button>
        </form>
        <button type="button" class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#abrsm-modal">ABRSM levels</button>
        <button type="button" class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#rcm-modal">RCM levels</button>
    </div>
@endcomponent
