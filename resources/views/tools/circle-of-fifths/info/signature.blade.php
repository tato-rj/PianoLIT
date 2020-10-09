@component('components.modal', [
	'id' => 'modal-key-signature', 
	'options' => [
		'size' => 'lg',
		'header' => ['border' => true]
	]])

	@slot('header')
	Key Signature
	@endslot

	@slot('body')
	<p>The Key Signature is where the composer will indicate which notes (if any) are sharps or flats. That indication follows a specific order and it tells you what is the <strong>key</strong> the piece. You can find the Key Signature located at the beginning of the score, right after the clefs.</p>

	@include('tools.circle-of-fifths.info.link', ['link' => 'https://en.wikipedia.org/wiki/Key_signature'])

	<h6 class="text-teal">Two keys for every Key Signature</h6>
	
	<p>For every major key, there is one other minor key that <strong>shares the exact same notes</strong> and vice-versa: each major key has a <i>relative minor</i> and each minor key has a <i>relative major</i>. For that reason they both share the same <i>key signature</i>, having the exact same sharp notes or flats notes in it.</p>

	@include('tools.circle-of-fifths.info.link', ['link' => 'https://en.wikipedia.org/wiki/Relative_key'])
	@endslot
@endcomponent