@component('components.overlays.modal', ['title' => 'Neighbor Keys', 'size' => 'lg'])
	@include('tools.circle.info.title')
	
	<p>The <i>neighbor keys</i> are those keys that have only <strong>one note different from the main key</strong>, therefore are very similar and considered to be close. Look at another step to the right or left in the Circle of Fifths and you will find keys that have 2 notes different, so these are more <i>distant</i> from the main key. The more steps you move in either direction, the further away the keys get from the main key (the more different they will sound).</p>
	
	@include('tools.circle.info.title', ['title' => 'Why does this matter?'])
	<p>In most pieces you can expect to see the neighbor chords much more often than any other ones, that is why it is helpful to know where they are in any given key. For example, in the key of F major, one should expect to see chords such as G minor, A minor, Bb major, and C major.</p>
	<p>Also, when composers <i>modulate</i> (meaning when they change the key of the piece), most often they will move into one of the neighbor keys, simply because they have only one note that is different. That leads to a smoother and elegant modulation. For example, in a piece in G major you might find a modulation to D major, which is the 5th degree in the scale and one of the neighbors keys with only one note difference.</p>
	<p>There are, however, many examples of pieces that modulate to a distant key in a very elegant way as well. That only shows the artistry and skill of the composer, since the farther away from the home key we go, the more difficult it is to do that.</p>

	@include('tools.circle.info.link', ['link' => 'https://en.wikipedia.org/wiki/Modulation_(music)'])
@endcomponent