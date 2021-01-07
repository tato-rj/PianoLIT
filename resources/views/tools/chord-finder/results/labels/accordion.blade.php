<div class="col-lg-8 col-12">
	<div class="accordion" id="chord-accordion-{{$index}}">
		@foreach(['root', 'type', 'sus', 'seventh', 'others', 'bass'] as $view)
		@include('tools.chord-finder.results.labels.' . $view, ['index' => $index, 'step' => $loop->iteration, 'chordlabel' => $chordlabel])
		@endforeach
	</div>
</div>