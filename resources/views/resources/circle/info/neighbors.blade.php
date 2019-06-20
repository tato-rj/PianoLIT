@component('components.overlays.modal', ['title' => 'Neighbor Keys', 'size' => 'lg', 'feedback' => true])
	@include('resources.circle.info.title')
	
	<p>The <i>neighbor keys</i> are those keys that have only <strong>one note different from the main key</strong>, therefore are very similar and considered to be very close. More one more step to the right or left in the Circle of Fifths and you will find keys that have 2 notes different, so these are more distant from the main key. The more steps you move in either direction, the further away the keys get from the main key (the more different they will sound).</p>
	@include('resources.circle.info.title', ['title' => 'Why does this matter?'])
	<p>In most pieces you can expect to see the neighbor chords much more often than any other ones, that is why it is helpful to know where they are in any given key. For example, in the key of F major, one should expect to see chords such as G minor, A minor, Bb major, and C major.</p>
	<p>Also, when composers <a href="#" target="_blank" class="link-blue" title="Lean more about this">modulate</a> (meaning when they change the key of the piece), most often they will move into one of the neighbor keys, simply because they have only one note that is different. That leads to a smoother and elegant modulation. For example, in a piece in G major you might find a modulation to D major, which is the 5th degree in the scale and one of the neighbos keys with only one note difference.</p>

	@include('resources.circle.info.link')
@endcomponent