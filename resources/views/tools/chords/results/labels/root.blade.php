<span class="badge alert-red m-0">STEP 1: INTERVALS</span>
<div class="p-2 text-muted">
	<p>In order to figure out the name of the chord we consider only the <u>intervals between the root (the first note) and each of the other notes</u>.</p>
	<p>Here, the <strong>root {{chordToHumans($inversion['chord'][0])}}</strong> forms 
		@foreach($inversion['chord'] as $index => $note)
		@if(! $loop->last)
		a <strong>{{$inversion['intervals'][$index]['name']}}</strong> with the {{iterationToHumans($loop->iteration)}} note {{chordToHumans($inversion['chord'][$loop->iteration])}}{{$inversion['intervals'][$index]['interval'] > 8 ? ' (an octave above)' : null}}{{$loop->iteration < count($inversion['chord']) - 1 ? ',' : '.'}}
		@endif
		@endforeach
	</p>
</div>