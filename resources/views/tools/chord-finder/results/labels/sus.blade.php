@component('tools.chord-finder.results.labels.accordion-cell', ['title' => 'ADD OR SUS', 'index' => $index, 'step' => $step])
	<p>Next, if there is a 2nd and/or a 4th, we'll indicate that by saying this is an <strong>add</strong> or <strong>sus</strong> chord. How do we know which one? If, besides having a 2nd and/or a 4th, the chord also has a 3rd we call it <u>add</u> (as an <u>add</u>ed note), it there isn't a 3rd we call it <u>sus</u> (as in <u>sus</u>pended).</p>
	@php
	$second = (new \App\Resources\ChordFinder\Label([]))->find($inversion, 2);
	$fourth = (new \App\Resources\ChordFinder\Label([]))->find($inversion, 4);
	@endphp
	@if (! $second && ! $fourth)
	<p class="m-0">This chord <strong>has no 2nd or 4th</strong>, so it is neither add nor sus.</p>
	@else
	<p class="m-0">In this chord we find a  
		{!! $second ? ' <strong>' . $second['name'] . '</strong>' : null !!} 
		{{ $fourth && $second ? ' and a' : null}}
		{!! $fourth ? ' <strong>' . $fourth['name'] . '</strong>' : null !!}. 
		That is why we call it <strong>{{strip_tags($inversion['label']['sus_shorthand'])}}</strong>.
	</p>
	@endif
@endcomponent