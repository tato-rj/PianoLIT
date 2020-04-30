<div>
	<button class="btn-raw text-muted d-inline" title="Expand player" id="expand-player">@fa(['size' => 'lg', 'icon' => 'expand'])</button>
</div>
<div class="flex-grow clamp-1">
	<strong>{{$piece->medium_name}}</strong>
	<span class="text-muted" id="speed-label"></span>
</div>
<div class="d-flex align-items-center">
	<button class="btn-raw text-muted" title="Toggle player" id="toggle-player">@fa(['size' => 'lg', 'icon' => 'chevron-down', 'mr' => 3])</button>
	<button class="btn-raw text-muted" title="Close player" data-player="audio-control" id="close-player">@fa(['size' => 'lg', 'icon' => 'times', 'mr' => 0])</button>
</div>