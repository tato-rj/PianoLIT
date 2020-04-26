@component('webapp.search.options.layout', ['id' => 'sort-container'])
<div class="d-flex text-muted options-columns" id="sort-container">
	@include('webapp.search.options.sort.option', [
		'label' => 'SORT BY LEVEL',
		'name' => 'level',
		'options' => [
			'Easy to difficult' => 'asc',
			'Difficult to easy' => 'desc'
		],
	])

	@include('webapp.search.options.sort.option', [
		'label' => 'SORT BY PIECE',
		'name' => 'name',
		'options' => [
			'Title A to Z' => 'asc',
			'Title Z to A' => 'desc'
		],
	])

	@include('webapp.search.options.sort.option', [
		'label' => 'SORT BY CATALOGUE',
		'name' => 'catalogue',
		'options' => [
			'Lower to higher' => 'asc',
			'Higher to lower' => 'desc'
		],
	])

	@include('webapp.search.options.sort.option', [
		'label' => 'SORT BY COMPOSER',
		'name' => 'composer',
		'options' => [
			'Name A to Z' => 'asc',
			'Name Z to A' => 'desc'
		],
	])

	@include('webapp.search.options.sort.option', [
		'label' => 'SORT BY VIEWS',
		'name' => 'views',
		'options' => [
			'Most popular' => 'desc',
			'Least popular' => 'asc'
		],
	])
</div>
@endcomponent