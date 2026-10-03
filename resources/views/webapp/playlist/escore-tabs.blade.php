<div class="escore-preview-tabs" aria-label="Preview sections">
    @foreach(['cover' => 'Cover', 'index' => 'Table of contents', 'pieces' => 'Piece pages'] as $key => $label)
    <button class="btn btn-secondary btn-sm {{ $loop->first ? 'active' : '' }}" type="button" data-escore-preview-tab="{{ $key }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}">{{ $label }}</button>
    @endforeach
</div>
