<div id="audio-player">
	<div id="player-header" class="d-flex d-apart badge p-0 p-2">
		<div class="">
			<strong>{{$piece->medium_name}}</strong>
		</div>
		<div class="d-flex align-items-center">
			<button class="btn-raw" id="toggle-player">@fa(['size' => 'lg', 'icon' => 'chevron-down'])</button>
			<button class="btn-raw" data-player="audio-control" id="close-player">@fa(['size' => 'lg', 'icon' => 'times', 'mr' => 0])</button>
		</div>
	</div>
	<div id="player-body" class="p-3 border-top">
		<div class="d-flex mb-3">
			<div class="text-muted">
				<small><strong><span id="speed-label"></span>Normal speed</strong></small>
			</div>
			<div class="flex-grow mx-3 px-3 border-x d-flex align-items-center">
				<span class="badge badge-light">slow</span>
				<input class="custom-range w-100 mx-2" id="audio-speed" type="range" value="1" min="0.25" max="1.85" step="0.25">
				<span class="badge badge-light">fast</span>
			</div>
			<div class="d-flex align-items-center">
				<button class="btn-raw">@fa(['size' => 'lg', 'icon' => 'hand-paper', 'classes' => 'mirror', 'mr' => 0, 'color' => 'grey'])</button>
				<button class="btn-raw">@fa(['size' => 'lg', 'icon' => 'hand-paper', 'mr' => 0, 'ml' => 2, 'color' => 'grey'])</button>
			</div>
		</div>
		<div>
			<audio id="audio-control" controls class="w-100">
				<source src="{{storage($piece->audio_path)}}" type="audio/mp3">
			</audio>
		</div>
	</div>
</div>