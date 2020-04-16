@component('tools.chord-finder.results.labels.accordion-cell', ['title' => 'OTHER INTERVALS', 'index' => $index, 'step' => $step])
	<p>Finally, let's look for any other intervals, such as a <strong>6th</strong>, a <strong>9th</strong>, an <strong>11th</strong>, etc. For each one, we'll add that note the chord according to the corresponding interval.</p>
	@php
	$dissonances = [];

	foreach ([6,9,11,13] as $dissonance) {
		$result = (new \App\Resources\ChordFinder\Label([]))->find($inversion, $dissonance);
		if ($result)
			array_push($dissonances, $result);
	}
	@endphp
	@if (empty($dissonances))
		<p class="m-0">This chord <strong>has no other dissonances</strong>.</p>
	@else
		<p class="m-0">In this case we have 
		@foreach($dissonances as $dissonance)
		{!! 'a <strong>'.$dissonance['name'].'</strong>' !!}{{$loop->iteration < count($dissonances) - 1 ? ', ' : null}}{{$loop->iteration == count($dissonances) - 1 ? ' and ' : null}}{{$loop->last ? '.' : null}}
		@endforeach
		</p>
	@endif
@endcomponent