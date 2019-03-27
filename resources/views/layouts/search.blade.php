@component('components.overlay', ['name' => 'search', 'opacity' => 1])
<div class="container py-5">
	<div class="row">
		<div class="col-lg-8 col-12 mx-auto">
			<input type="text" name="search" class="border-bottom p-4 text-lead rounded-0 bg-transparent border-grey w-100 mb-4" 
				style="font-size: 3em; border-top: 0; border-left: 0; border-right: 0;" placeholder="What's on your mind?">
		</div>
	</div>
</div>
@endcomponent
