@if($product->audio_path)
<button data-action="play" data-src="{{storage($product->audio_path)}}" class="btn btn-block btn-outline-secondary mb-2">@fa(['icon' => 'play'])Tap to listen</a>
<button data-action="stop" class="btn btn-block btn-secondary mb-2" style="display: none;">@fa(['icon' => 'stop'])Stop audio</a>
@endif