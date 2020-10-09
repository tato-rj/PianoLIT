@component('components.modal', [
	'id' => 'modal-closely-related-keys', 
	'options' => [
		'size' => 'lg',
		'header' => ['border' => true]
	]])

	@slot('header')
	Closely Related Keys
	@endslot
	
	@slot('body')
	<p>The <i>closely related keys</i> are those keys that have only <strong>one note different from the main key</strong>, therefore are very similar and considered to be close. The more steps you move in either direction on the circle of fifths, the more distant the keys get from the main key (the more different they will sound).</p>
	
	<h6 class="text-teal">Why does this matter?</h6>
	
	<p>In most pieces you can expect to see the closely related chords much more often than any other ones, that is why it is helpful to know where they are in any given key. For example, in the key of F major, one should expect to see chords such as G minor, A minor, Bb major, and C major.</p>
	
	<p>If a piece modulates (changes keys), chances are it will move to one of its closely related keys. This is because you just need to <u>change one note to move into the new key</u>, making it easier and seamless. It is not at all uncommon to modulate to more distant keys, of course, but it is more difficult to do that. Composers will often move gradually, going from one closely related key to the next, until they reach the distant new key.</p>

	@include('tools.circle-of-fifths.info.link', ['link' => 'https://en.wikipedia.org/wiki/Modulation_(music)'])
	@endslot
@endcomponent