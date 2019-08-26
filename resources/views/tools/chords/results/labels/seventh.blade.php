@component('tools.chords.results.labels.accordion-cell', ['title' => 'THE 7th', 'index' => $index, 'step' => $step])
	<p>We also check if there is a <strong>7th</strong>. If yes, we will add the 7th to the chord according to the corresponding interval.</p>
	@php
	$seventh = (new \App\Resources\ChordFinder\Label([]))->find($inversion, 7);
	@endphp
	@if (! $seventh)
		<p>This chord <strong>has no 7th</strong>.</p>
	@else
		<p>In this case, we have a {!! '<strong>' . $seventh['name'] . '</strong>' !!}. 
			@if($inversion['label']['type'] == 'fully diminished')
			We also refer to a diminished chord that has a diminished 7th (a normal minor 7th lowered one step further down) as a <strong>fully diminished chord</strong>, which is the case here.
			@elseif($inversion['label']['type'] == 'half diminished')
			We also refer to a diminished chord that has a minor 7th as a <strong>half diminished chord</strong>, which is the case here.
			@else
			The number 7 alone next to the chord usually implies a minor 7th, if it is major you'll see M7, or +7, or even maj7.
			@endif
			</p>
		@if($inversion['label']['type'] == 'major' && $seventh['type'] == 'minor')
		<p>We also call this a <strong>dominant chord</strong>, because it is major and has a minor 7th. Check out our <a href="{{route('tools.circle-of-fifths')}}" target="_blank">Circle of Fifths</a> page to learn more about functional harmony.</p>
		@endif
	@endif
@endcomponent