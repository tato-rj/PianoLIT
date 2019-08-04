<span class="badge alert-red m-0">STEP 2: 3rd AND 5th</span>
<div class="p-2 text-muted">
	<p>Once each interval has been named, we first need to determine if the chord is major, minor, diminished or augmented. To do that, all we need is the <strong>3rd</strong> and the <strong>5th</strong>.</p>
	@php
		$third = (new \App\Resources\ChordFinder\Label([]))->find($inversion, 3);
		$fifth = (new \App\Resources\ChordFinder\Label([]))->find($inversion, 5);

		if ($third) {
			$thirdType = 'has a <strong>' . $third['name'] . '</strong>';
		} elseif ($fifth['type'] == 'diminished') {
			$thirdType = '<strong>is missing the 3rd</strong>, but it has a diminished 5th, so we treat it as having a minor 3rd'
		} else {
			$thirdType = '<strong>is missing the 3rd</strong>, so we treat it as having a major 3rd';
		}
	@endphp
	<p>In this case, the chord 
		{!! $thirdType !!} 
		and it 
		{!! $fifth ? 'has a <strong>' . $fifth['name'] . '</strong>' : '<strong>is missing the 5th</strong>, so we assume it is a perfect 5th' !!}. 
		That is why we consider it <strong>{{$inversion['label']['type'] ?? 'major'}}</strong>.
	</p>
</div>