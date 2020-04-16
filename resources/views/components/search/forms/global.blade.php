@component('layouts.overlay', ['name' => 'global-search', 'light' => true, 'background' => '255,255,255,1', 'window_bg' => 'white', 'position' => 'top'])
<div class="container py-5">
	<div class="row">
		<div class="col-lg-10 col-12 mx-auto">
			<input autocomplete="off" type="text" id="global-search-input" class="border-bottom p-4 text-lead rounded-0 bg-transparent border-grey w-100 mb-4" 
				style="font-size: 3em; border-top: 0; border-left: 0; border-right: 0;" placeholder="What's on your mind?" data-url="{{route('search.global')}}">
		</div>
		
		<div class="col-12 text-center" id="global-search-feedback">
			<div class="text-muted" loading style="display: none;">@fa(['icon' => 'hourglass-half'])<i>Searching...</i></div>
			<div error style="display: none;">
				<p class="text-muted"><i>Ops, we're having some problems...</i></p>
				<p>If this issue persists, please let us know at <a href="mailto:contact@pianolit.com">contact@pianolit.com</a></p>
			</div>
		</div>

		<div class="col-lg-10 col-12 mx-auto pb-4" id="global-search-results"></div>
	</div>
</div>
@endcomponent