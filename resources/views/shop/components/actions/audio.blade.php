@if($product->audio_path)
<button type="button" data-product-audio data-src="{{ storage($product->audio_path) }}" class="{{ $actionClasses ?? 'btn d-block w-100 btn-secondary' }} mb-{{ $mb ?? 2 }}" aria-pressed="false">
    <span data-audio-play>@icon('play')Listen to a sample</span>
    <span data-audio-stop hidden>@icon('square')Stop sample</span>
    <span data-audio-loading hidden>Loading audio…</span>
</button>
<div data-audio-error class="w-100" hidden>
    @alert(['color' => 'red', 'message' => 'The audio sample could not be played. Try again.'])
</div>
@endif
