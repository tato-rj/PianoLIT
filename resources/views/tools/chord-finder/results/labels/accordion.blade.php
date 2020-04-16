<div class="px-2 w-100">
	<div class="accordion" id="chord-accordion-{{$index}}">
		@foreach(['root', 'type', 'sus', 'seventh', 'others', 'bass'] as $view)
		@include('tools.chord-finder.results.labels.' . $view, ['index' => $index, 'step' => $loop->iteration, 'chordlabel' => $chordlabel])
		@endforeach
	</div>
</div>