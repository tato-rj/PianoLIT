<section id="options-container" class="sticky-top bg-white px-2 pt-2" style="z-index: 1;">
	<div class="mb-2 d-flex justify-content-end" style="display: none;" id="options">
		@button(['disabled' => $disabled, 'label' => \App\Support\Icon::render('arrow-down-up', ['mr' => 1]) . ' Sort by', 'attr' => 'data-target=#sort-container',
		'styles' => [
			'size' => 'sm', 'theme' => 'secondary'],
			'classes' => 'me-2'])
		@button(['disabled' => $disabled, 'label' => \App\Support\Icon::render('filter', ['mr' => 1]) . ' Filter by', 'attr' => 'data-target=#filters-container',
		'styles' => [
			'size' => 'sm', 'theme' => 'secondary']])
	</div>

	<div id="{{$env ?? 'server'}}-filter">
		@include('webapp.components.sorting.sort')
		@include('webapp.components.sorting.filter')
	</div>
</section>