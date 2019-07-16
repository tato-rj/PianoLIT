<div>
	<div style="height: 160px" class="d-flex keyboard">
		@foreach($octaves as $octave => $highlights)
			@include('components.piano.octave', ['octave' => $octave, 'highlights' => $highlights])
		@endforeach
	</div>
</div>