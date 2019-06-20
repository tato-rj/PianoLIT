@component('components.overlays.modal', ['title' => 'Relative Key', 'size' => 'lg', 'feedback' => true])
	@include('resources.circle.info.title')
	
	<p>For every major key there is one other minor key that <strong>shares the exact same notes</strong> and vice-versa: each major key has a <i>relative minor</i> and each minor key has a <i>relative major</i>. For that reason they both share the same <a href="#" target="_blank" class="link-blue" title="Lean more about this">key signature</a>, having the exact same sharp notes or flats notes in it.</p>
	<p>On major keys, the relative minor is the scale starting on the 6th note above the tonic. For example, in C major the relative minor will be A minor (C <span class="text-grey">- D - E - F - G -</span> <u>A</u>).</p>
	<p>On minor keys, the relative major is the scale starting on the 3rd note above the tonic. For example, in E minor the relative major will be G major (E <span class="text-grey">- F# -</span> <u>G</u>).</p>

	@include('resources.circle.info.link')
@endcomponent