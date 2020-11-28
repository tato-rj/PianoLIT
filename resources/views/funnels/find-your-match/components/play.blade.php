<div class="position-absolute audio-control text-center w-100">
	<button class="btn btn-light" data-action="play">
		@fa(['icon' => 'play'])Tap to listen
	</button>
	<button class="btn btn-light" data-action="pause" style="display: none;">
		@fa(['icon' => 'pause'])Pause audio
	</button>
	<audio style="display: none;">
		<source src="{{$audio}}" type="audio/mpeg">
	</audio>
</div>