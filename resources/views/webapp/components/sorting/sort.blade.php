@component('webapp.components.sorting.layout', ['id' => 'sort-container'])
	@include('webapp.components.sorting.option', [
		'label' => 'SORT BY LEVEL',
		'type' => 'radio',
		'name' => 'level',
		'options' => [
			'Easy to difficult' => 'asc',
			'Difficult to easy' => 'desc'
		],
	])

	@include('webapp.components.sorting.option', [
		'label' => 'SORT BY PIECE',
		'type' => 'radio',
		'name' => 'name',
		'options' => [
			'Title A to Z' => 'asc',
			'Title Z to A' => 'desc'
		],
	])

	@include('webapp.components.sorting.option', [
		'label' => 'SORT BY CATALOGUE',
		'type' => 'radio',
		'name' => 'catalogue',
		'options' => [
			'Lower to higher' => 'asc',
			'Higher to lower' => 'desc'
		],
	])

	@include('webapp.components.sorting.option', [
		'label' => 'SORT BY COMPOSER',
		'type' => 'radio',
		'name' => 'composer',
		'options' => [
			'Name A to Z' => 'asc',
			'Name Z to A' => 'desc'
		],
	])

	@include('webapp.components.sorting.option', [
		'label' => 'SORT BY PERIOD',
		'type' => 'radio',
		'name' => 'period',
		'options' => [
			'Old to modern' => 'asc',
			'Modern to old' => 'desc'
		],
	])

	@include('webapp.components.sorting.option', [
		'label' => 'SORT BY VIEWS',
		'type' => 'radio',
		'name' => 'views',
		'options' => [
			'Most popular' => 'desc',
			'Least popular' => 'asc'
		],
	])
@endcomponent