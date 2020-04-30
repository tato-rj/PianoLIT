<div id="audio-player">
	<div id="player-header" class="d-flex px-3 py-2 cursor-pointer">
		<div class="flex-grow">
			<strong>{{$piece->medium_name}}</strong>
			<span class="text-muted" id="speed-label"></span>
		</div>
		<div class="d-flex align-items-center">
			<button class="btn-raw text-muted" title="Toggle player" id="toggle-player">@fa(['size' => 'lg', 'icon' => 'chevron-down', 'mr' => 3])</button>
			<button class="btn-raw text-muted" title="Close player" data-player="audio-control" id="close-player">@fa(['size' => 'lg', 'icon' => 'times', 'mr' => 0])</button>
		</div>
	</div>
	<div id="player-body" class="p-3 border-top">
		<div class="d-flex mb-3">
			@if($piece->audio_lh && $piece->audio_rh)
			<div class="d-flex align-items-center mr-3 pr-3 border-right" id="select-hand" data-audio="{{$piece->audio}}">
				<button class="btn-raw text-muted opacity-4" data-audio="{{$piece->audio_lh}}" title="Left hand only">@fa(['size' => 'lg', 'icon' => 'hand-paper', 'classes' => 'mirror', 'mr' => 0])</button>
				<button class="btn-raw text-muted opacity-4" data-audio="{{$piece->audio_rh}}" title="Right hand only">@fa(['size' => 'lg', 'icon' => 'hand-paper', 'mr' => 0, 'ml' => 2])</button>
			</div>
			@endif
			<div class="flex-grow d-flex align-items-center">
				<span class="badge badge-light">slow</span>
				<input class="custom-range w-100 mx-2" id="audio-speed" type="range" value="1" min="0.25" max="1.85" step="0.25">
				<span class="badge badge-light">fast</span>
			</div>
		</div>
		<div>
			<audio id="audio-control" controls src="{{$piece->audio}}" type="audio/mp3" class="w-100">

			</audio>
		</div>
	</div>
</div>