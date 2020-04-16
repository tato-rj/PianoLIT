@component('components.overlays.modal', ['title' => 'Relative Key', 'size' => 'lg'])
	@include('tools.circle-of-fifths.info.title')
	
	<p>For every major key, there is one other minor key that <strong>shares the exact same notes</strong>: each major key has a <i>relative minor</i> and each minor key has a <i>relative major</i>. For that reason they both share the same <i>key signature</i>, having the exact same sharp or flats.</p>
	<p>On major keys, the relative minor is the scale starting on the <strong>6th note above the tonic</strong>. For example, in C major the relative minor will be A minor (C <span class="text-grey">- D - E - F - G -</span> <u>A</u>).</p>
	<p>On minor keys, the relative major is the scale starting on the <strong>3rd note above the tonic</strong>. For example, in E minor the relative major will be G major (E <span class="text-grey">- F# -</span> <u>G</u>).</p>

	@include('tools.circle-of-fifths.info.link', ['link' => 'https://en.wikipedia.org/wiki/Relative_key'])
@endcomponent