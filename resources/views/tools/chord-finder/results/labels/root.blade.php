@component('tools.chord-finder.results.labels.accordion-cell', ['title' => 'INTERVALS', 'index' => $index, 'step' => $step])
	<p>In order to figure out the name of the chord we consider only the <u>intervals between the root (the first note) and each of the other notes</u>.</p>
	@php($analysisNotes = $inversion['analysis_chord'] ?? $inversion['chord'])
	<p>Here, the <strong>root {{chordToHumans($analysisNotes[0])}}</strong> forms
		@foreach($analysisNotes as $index => $note)
		@if(! $loop->last)
		a <strong>{{$inversion['intervals'][$index]['name']}}</strong> with the {{iterationToHumans($loop->iteration)}} note {{chordToHumans($analysisNotes[$loop->iteration])}}{{$inversion['intervals'][$index]['interval'] > 8 ? ' (an octave above)' : null}}{{$loop->iteration < count($analysisNotes) - 1 ? ',' : '.'}}
		@endif
		@endforeach
	</p>
	<p class="m-0">Keep reading below to learn more about how we named this chord.</p>
@endcomponent
