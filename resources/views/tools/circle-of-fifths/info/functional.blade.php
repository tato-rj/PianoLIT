@component('components.modal', [
	'id' => 'modal-functional-harmony', 
	'options' => [
		'size' => 'lg',
		'header' => ['border' => true]
	]])
	
	@slot('header')
	Functional Harmony
	@endslot

	@slot('body')
	<p>Keys are a <strong>set of chords that follow a strict pattern</strong>. A musical scale has 7 notes and, to create the key, we build a triad (a 3-note chord) over each note of the scale. The major and minor scales have a specific set of intervals, which leads to the patterns we use most of the time.</p>

	<p>Music that is centered around a tonic (the first chord of a key) is called tonal music. In order to establish its tonal center, a scale needs three intervals: a <strong>perfect 4th</strong>, a <strong>perfect 5th</strong> and a <strong>major 7th</strong> (also called leading tone). The perfect 4th and 5th give the scale <i>stability</i>, for those are the two most consonant intervals after the octave. Both major and minor scales have these intervals, so we don't need to change them. The major 7th (last note of the scale, only a half step before the first) is also important because it <i>creates the tension needed to establish the home key</i>. As the nickname suggests, it <i>leads</i> our ears back to the home key. This interval is minor on minor scales and that is the reason why we raise the 7th on minor keys, making it a major 7th.</p>

	<h6 class="text-teal">The 3 main groups</h6>

	<p>If we have those 3 intervals in place, the home key can be established and the chords will then have <i>behavior</i>. That behavior, at the most basic level, has to do with <i>moving away and moving back</i> to the home key. The 3 main functions a chord can have are <strong>Tonic</strong>, <strong>Subdominant</strong>, <strong>Dominant</strong>. These functions are based on the 1st, 4th and 5th degree of the scale, respectively. Why these? Because they are the ones that never change, are consonant and give stability to the key.</p>

	<div class="bg-light rounded p-3 mb-3">
		<strong>Important!</strong> <span class="text-muted">This is not to be confused with the names of each degree in a scale, where the notes are labled: <i>tonic (1), supertonic (2), mediant (3), subdominant (4), dominant (5), submediant (6), subtonic (b7) and leading tone (7)</i>. The names of each function are <u>derived from the labels</u> of the first, fourth and fifth degrees in the scale.</span>
	</div>

	<p>At the most basic level, the chords in the Tonic group <strong>establish the home key</strong>; the chords in the Subdominant group <strong>move away from the home key</strong> and chords in the Dominant group <strong>move back to the key</strong>.</p>

	<h6 class="text-teal">Assigning a chord to its group</h6>

	<p>We assign the chords into each group by <u>similarity</u>. At a fundamental level, a chord has <i>3 notes</i> (also referred to as a triad). Two chords with 2 notes in common will belong to the same functional group. Our ears will perceive them as similar and they will have similar behavior inside their key. How strong that behavior is will vary between chords.</p>

	<p>For example, in the key of C major the 5th degree is G. Therefore, the dominant group in this key will start with G major (G - B - D). The other chords in this key that have 2 notes in common with G major are E minor (E - G - B) and B diminished (B - D -F). For that reason, all 3 chords will be able to behave as dominants in this key. How strong that behavior is will vary between chords: the G major is the strongest, closely followed by the B diminished and E minor will be the weakest dominant chord of all three in this example.</p>

	@include('tools.circle-of-fifths.info.link', ['link' => 'https://en.wikipedia.org/wiki/Function_(music)'])
	@endslot
@endcomponent
