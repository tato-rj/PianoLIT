@component('components.overlays.modal', ['title' => 'Key Signature', 'size' => 'lg'])
	@include('tools.circle.info.title')

	<p>When you look at the beginning of a music score, the first thing you see, before the notes, is the clef. Immediately following the clef there are two important pieces of information that will help you understand how the music is written: the <i>Key Signature</i> and the <i>Time Signature</i>. We are now looking at the first one, the Key Signature, where the composer will indicate which notes (if any) are sharps or flats. That indication follows a specific order and it tells you what is the <strong>key</strong> the piece.</p>

	@include('tools.circle.info.link', ['link' => 'https://en.wikipedia.org/wiki/Key_signature'])

	@include('tools.circle.info.title', ['title' => 'Two keys for every Key Signature'])
	<p>For every major key, there is one other minor key that <strong>shares the exact same notes</strong> and vice-versa: each major key has a <i>relative minor</i> and each minor key has a <i>relative major</i>. For that reason they both share the same <i>key signature</i>, having the exact same sharp notes or flats notes in it.</p>

	@include('tools.circle.info.link', ['link' => 'https://en.wikipedia.org/wiki/Relative_key'])
@endcomponent