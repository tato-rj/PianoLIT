@if($product->audio_path)
<button type="button" data-product-audio data-src="{{ storage($product->audio_path) }}" class="btn d-block w-100 btn-secondary mb-2" aria-pressed="false">
    <span data-audio-play>@icon('play')Listen to a sample</span>
    <span data-audio-stop hidden>@icon('square')Stop sample</span>
    <span data-audio-loading hidden>Loading audio…</span>
</button>
<p data-audio-error class="small text-danger mb-2" role="status" hidden>We couldn’t play this sample. Please try again.</p>
@endif
