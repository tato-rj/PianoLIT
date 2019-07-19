<div class="text-center">
	<div class="position-relative d-inline-block" style="max-width: 100%">
		<div style="height: 160px" class="d-flex {{$centered ? 'justify-content-center' : null}} keyboard">
			@foreach($octaves as $octave => $highlights)
				@include('components.piano.octave', ['octave' => $octave, 'highlights' => $highlights, 'styles' => $loop->iteration == 3 ? 'hide-on-sm' : null])
			@endforeach
		</div>
	</div>
</div>