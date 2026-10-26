<div id="play" class="mb-3">
	{{-- <div class="d-center h-100"> --}}
		<div class="btn-floating w-100">
			<button type="button" action="play" class="btn btn-primary w-100">@include('games.challenges.components.icon', ['icon' => 'play']){{$playLabel ?? 'Play'}}</button>
		</div>
		<div class="btn-floating w-100">
			<button type="button" action="stop" style="display: none" class="btn btn-secondary w-100">@include('games.challenges.components.icon', ['icon' => 'stop'])Stop</button>
		</div>
	{{-- </div> --}}
</div>
