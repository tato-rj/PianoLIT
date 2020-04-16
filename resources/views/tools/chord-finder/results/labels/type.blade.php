@component('tools.chord-finder.results.labels.accordion-cell', ['title' => '3rd AND 5th', 'index' => $index, 'step' => $step])
	<p>Once each interval has been named, we first need to determine if the chord is major, minor, diminished or augmented. To do that, all we need is the <strong>3rd</strong> and the <strong>5th</strong>.</p>
	@php
		$third = (new \App\Resources\ChordFinder\Label([]))->find($inversion, 3);
		$fifth = (new \App\Resources\ChordFinder\Label([]))->find($inversion, 5);
		$tenth = (new \App\Resources\ChordFinder\Label([]))->find($inversion, 10);
		$twelveth = (new \App\Resources\ChordFinder\Label([]))->find($inversion, 12);

		if ($tenth) {
			$type = 'has a <strong>' . str_replace('10', '3', $tenth['name']) . '</strong> (we see it an octave above as a 10th, but this will still count as a 3rd)';
		} elseif ($third) {
			$type = 'has a <strong>' . $third['name'] . '</strong>';
		} elseif ($fifth['type'] == 'diminished') {
			$type = '<strong>is missing the 3rd</strong>, but it has a <strong>diminished 5th</strong>, so we treat it as having a minor 3rd';
		} elseif ($twelveth['type'] == 'diminished') {
			$type = '<strong>is missing the 3rd</strong>, but it has a <strong>diminished 5th</strong> (we see it an octave above as a 12th, but this will still count as a 5th), so we treat it as having a minor 3rd';
		} else {
			$type = '<strong>is missing the 3rd</strong>, so we treat it as having a major 3rd';
		}

		if (!$fifth && !$twelveth) {
			$type .= ' but it <strong>is missing the 5th</strong> (in such cases we assume it is a perfect 5th)';
		} elseif ($twelveth && !$fifth) {
			$type .= ' and a <strong>' . str_replace('12', '5', $twelveth['name']) . '</strong> (we see it an octave above as a 12th, but this will still count as a 5th)';
		} else {
			$type .= ' and a <strong>' . $fifth['name'] . '</strong>';
		}
	@endphp
	<p class="m-0">In this case, the chord {!! $type !!}. That's why we say this is a <strong>{{$inversion['label']['type'] ? lastword($inversion['label']['type']) : 'major'}}</strong> chord.</p>

	@if($chordlabel['type'] == 'half diminished')
	<p class="mb-0 mt-3">A chord with a diminished 5th and minor 7th is also referred to as a <u>minor 7th with a flat 5</u>. In this case, we can call this chord a <strong class="text-nowrap">{{$chordlabel['root']}} {{$chordlabel['seventh']}} b5</strong>. <i class="text-muted">(special thanks to our user Paul for pointing that out!)</i></p>
	@endif
@endcomponent