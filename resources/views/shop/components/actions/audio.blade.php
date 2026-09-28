@if($product->audio_path)
<button data-action="play" data-src="{{storage($product->audio_path)}}" class="btn btn-block btn-outline-secondary mb-2">@icon('play')Tap to listen</a>
<button data-action="stop" class="btn btn-block btn-secondary mb-2" style="display: none;">@icon('square')Stop audio</a>
@endif