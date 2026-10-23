<div class="d-flex align-items-center gap-1" data-sound-row data-voice-id="{{ $recording['id'] }}">
    <button type="button" class="dropdown-item flex-grow-1 text-truncate @if($selected ?? false) active @endif" data-sound-choice data-sound-value="voice:{{ $recording['id'] }}" aria-pressed="{{ ($selected ?? false) ? 'true' : 'false' }}">{{ $recording['name'] }}</button>
    <button type="button" class="btn btn-secondary btn-sm p-2 flex-shrink-0" data-sound-play aria-label="Play {{ $recording['name'] }}" title="Play recording">
        <span data-sound-play-icon>@icon('play', ['mr' => 0])</span>
        <span data-sound-stop-icon hidden>@icon('square', ['mr' => 0])</span>
    </button>
    <button type="button" class="btn btn-secondary btn-sm p-2 flex-shrink-0" data-sound-edit aria-label="Rename {{ $recording['name'] }}" title="Rename recording">@icon('square-pen', ['mr' => 0])</button>
    <button type="button" class="btn btn-secondary btn-sm p-2 flex-shrink-0 text-danger" data-sound-delete aria-label="Delete {{ $recording['name'] }}" title="Delete recording">@icon('trash-2', ['mr' => 0])</button>
</div>
