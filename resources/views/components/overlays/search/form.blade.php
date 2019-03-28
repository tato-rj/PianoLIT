@component('layouts.overlay', ['name' => 'search', 'opacity' => 1, 'position' => 'top'])
<div class="container py-5">
	<div class="row">
		<div class="col-lg-10 col-12 mx-auto">
			<input type="text" name="search" class="border-bottom p-4 text-lead rounded-0 bg-transparent border-grey w-100 mb-4" 
				style="font-size: 3em; border-top: 0; border-left: 0; border-right: 0;" placeholder="What's on your mind?" data-url="{{route('api.blog.search')}}">
		</div>
		<div class="col-12 text-center" id="search-feedback">
			<div class="text-muted" style="display: none;"><i>Searching...</i></div>
		</div>
		<div class="col-lg-10 col-12 mx-auto" id="search-results">
			
		</div>
	</div>
</div>
@endcomponent