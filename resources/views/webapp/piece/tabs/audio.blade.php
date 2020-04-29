<div class="tab-pane fade show active" id="tab-audio">
	@if($piece->hasAudio())
	<div class="mb-4">
		<h5 class="mb-4">PianoLIT recording</h5>
		<div class="text-center">
			<button class="btn rounded-pill btn-default" data-toggle="modal" data-target="#play-modal">
				@fa(['icon' => 'play-circle', 'fa_type' => 'r'])Listen now</button>
		</div>
	</div>
	@endif

	<div class="mb-4">
		<h5 class="mb-4">@fa(['icon' => 'apple', 'fa_type' => 'b'])Music recordings</h5>
		@if($piece->hasITunes())
			@foreach($piece->itunes_array as $itunes)
			@include('webapp.piece.components.apple-music', compact('piece'))
			@endforeach
		@else
			<div class="text-center text-grey">
				<h1>@fa(['icon' => 'itunes-note', 'fa_type' => 'b'])</h1>
				<p>No available recordings on @fa(['icon' => 'apple', 'fa_type' => 'b'])Music.</p>
			</div>
		@endif
	</div>
</div>