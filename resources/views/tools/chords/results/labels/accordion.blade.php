<div class="px-2 w-100">
	<div class="accordion" id="chord-accordion-{{$index}}">
		@foreach(['root', 'type', 'sus', 'seventh', 'others', 'bass'] as $view)
		@include('tools.chords.results.labels.' . $view, ['index' => $index, 'step' => $loop->iteration])
		@endforeach
	</div>
</div>