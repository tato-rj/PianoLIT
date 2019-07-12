<div class="keyboard">
	<div style="height: 160px" class="d-flex justify-content-center">
		@include('components.piano.octave', ['highlights' => [
			[true, false],
			[true, false],
			[true, false],
			[true, false],
			[true, false],
			[true, false],
			[true, false]
		], 'octave' => 3])
		@include('components.piano.octave', ['octave' => 4])
	</div>
</div>