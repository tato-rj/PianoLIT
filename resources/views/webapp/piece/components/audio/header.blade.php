@if($piece->hasSeparateHandsAudio())
<div>
	<button class="btn-raw text-muted d-inline" title="Expand player" id="expand-player">@icon('maximize', ['size' => 'lg'])</button>
</div>
@endif
<div class="flex-grow clamp-1">
	<strong>{{$piece->medium_name}}</strong>
	<span class="text-muted" id="speed-label"></span>
</div>
<div class="d-flex align-items-center">
	<button class="btn-raw text-muted" title="Toggle player" id="toggle-player">@icon('chevron-down', ['size' => 'lg', 'mr' => 3])</button>
	<button class="btn-raw text-muted" title="Close player" data-dismiss="popup" data-player="audio-control" id="close-player">@icon('close', ['size' => 'lg', 'mr' => 0])</button>
</div>