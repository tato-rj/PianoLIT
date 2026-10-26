<div class="text-center" @if($designSystemPreview ?? false) data-ds-preview @endif>
	<div class="position-relative {{ ($responsive ?? false) ? 'keyboard-responsive d-block mx-auto' : 'd-inline-block' }}" style="max-width: 100%; @if($responsive ?? false) width: {{ count($octaves) * 280 }}px; --piano-key-count: {{ count($octaves) * 7 }}; @endif">
		<div style="height: {{ ($responsive ?? false) ? 'var(--piano-height)' : '160px' }}" class="d-flex {{$centered ? 'justify-content-center' : null}} keyboard">
			@foreach($octaves as $octave => $highlights)
				@include('components.music.piano.octave', ['octave' => $octave, 'highlights' => $highlights, 'styles' => $loop->iteration == 3 ? 'hide-on-sm' : null])
			@endforeach
		</div>
	</div>
</div>