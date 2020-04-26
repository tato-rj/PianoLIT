<div class="mb-2 d-flex justify-content-end" style="display: none;" id="options">
	@button(['disabled' => $disabled, 'label' => '<i class="fas fa-sort mr-1"></i> Sort by', 'attr' => 'data-target=#sort-container', 'size' => 'sm', 'theme' => 'outline-secondary', 'classes' => 'mr-2'])
	@button(['disabled' => $disabled, 'label' => '<i class="fas fa-filter mr-1"></i> Filter by', 'attr' => 'data-target=#filters-container', 'size' => 'sm', 'theme' => 'outline-secondary'])
</div>

<div>
	@include('webapp.search.options.sort.index')
	@include('webapp.search.options.filters.index')
</div>