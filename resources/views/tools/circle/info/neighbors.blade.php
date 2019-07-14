@component('components.overlays.modal', ['title' => 'Neighbor Keys', 'size' => 'lg'])
	@include('tools.circle.info.title')
	
	<p>The <i>closely related keys</i> are those keys that have only <strong>one note different from the main key</strong>, therefore are very similar and considered to be close. The more steps you move in either direction on the circle of fifths, the more distant the keys get from the main key (the more different they will sound).</p>
	
	@include('tools.circle.info.title', ['title' => 'Why does this matter?'])
	<p>In most pieces you can expect to see the closely related chords much more often than any other ones, that is why it is helpful to know where they are in any given key. For example, in the key of F major, one should expect to see chords such as G minor, A minor, Bb major, and C major.</p>
	
	<p>If a piece modulates (changes keys), chances are it will move to one of its closely related keys. This is because you just need to <u>change one note to move into the new key</u>, making it easier and seamless. It is not at all uncommon to modulate to more distant keys, of course, but it is more difficult to do that. Composers will often move gradually, going from one closely related key to the next, until they reach the distant new key.</p>

	@include('tools.circle.info.link', ['link' => 'https://en.wikipedia.org/wiki/Modulation_(music)'])
@endcomponent