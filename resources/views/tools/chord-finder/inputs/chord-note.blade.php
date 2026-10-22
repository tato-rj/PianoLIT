<div class="chord-note" data-letter="{{ $note }}" data-octave="{{ $octave }}" data-steps="0">
    <button type="button" class="btn btn-secondary w-100 mb-2 chord-note-toggle" aria-label="Select {{ $note }}" aria-pressed="false"><span data-letter-label>{{ $note }}</span><span data-accidental-label></span></button>
    <div class="d-flex gap-1">
        <button type="button" class="btn btn-secondary btn-sm flex-fill" data-step="-1" aria-label="Lower {{ $note }}" disabled>♭</button>
        <button type="button" class="btn btn-secondary btn-sm flex-fill" data-step="1" aria-label="Raise {{ $note }}" disabled>♯</button>
    </div>
</div>
