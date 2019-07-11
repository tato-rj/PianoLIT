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
		]])
		@include('components.piano.octave')
	</div>
</div>