@if($request['strict'] && $inversion['label']['bass'] && $inversion['label']['bass'][0] != $inversion['label']['root'][0])
@component('tools.chords.results.labels.accordion-cell', ['title' => 'BASS', 'index' => $index, 'step' => $step])
	<p>If you want a specific note (other than the root) to be in the bass, you can simply add a "/" after the chord and the note you want as a bass.</p>
	<p class="m-0">Here, the <strong>bass {{chordToHumans($request['chords'][0]['notes'][0])}}</strong> is indicated at the end of the chord name.</p>
@endcomponent
@endif