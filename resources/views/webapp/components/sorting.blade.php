<section id="options-container" class="sticky-top bg-white px-2 pt-2">
	<div class="mb-2 d-flex justify-content-end" style="display: none;" id="options">
		@button(['disabled' => $disabled, 'label' => '<i class="fas fa-sort mr-1"></i> Sort by', 'attr' => 'data-target=#sort-container', 
		'styles' => [
			'size' => 'sm', 'theme' => 'outline-secondary'], 
			'classes' => 'mr-2'])
		@button(['disabled' => $disabled, 'label' => '<i class="fas fa-filter mr-1"></i> Filter by', 'attr' => 'data-target=#filters-container', 
		'styles' => [
			'size' => 'sm', 'theme' => 'outline-secondary']])
	</div>

	<div id="{{$env ?? 'server'}}-filter">
		@include('webapp.components.sorting.sort')
		@include('webapp.components.sorting.filter')
	</div>
</section>