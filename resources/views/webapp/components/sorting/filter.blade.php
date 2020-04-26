@component('webapp.components.sorting.layout', ['id' => 'filters-container'])
	@include('webapp.components.sorting.option', [
		'label' => 'FILTER BY LEVEL',
		'type' => 'checkbox',
		'name' => 'level',
		'options' => [
			'Elementary' => 'elementary',
			'Early beginner' => 'early beginner',
			'Late beginner' => 'late beginner',
			'Early intermediate' => 'early intermediate',
			'Late intermediate' => 'late intermediate',
			'Advanced' => 'advanced',
		],
	])

	@include('webapp.components.sorting.option', [
		'label' => 'FILTER BY PERIOD',
		'type' => 'checkbox',
		'name' => 'period',
		'options' => [
			'Baroque' => 'baroque',
			'Classical' => 'classical',
			'Romantic' => 'romantic',
			'Impressionist' => 'impressionist',
			'Modern' => 'modern',
		],
	])

	@include('webapp.components.sorting.option', [
		'label' => 'FILTER BY LENGTH',
		'type' => 'checkbox',
		'name' => 'length',
		'options' => [
			'Short' => 'short',
			'Medium' => 'medium',
			'Long' => 'long'
		],
	])

	@include('webapp.components.sorting.option', [
		'label' => 'FILTER BY TYPE',
		'type' => 'checkbox',
		'name' => 'type',
		'options' => [
			'Pedagogical' => 'pedagogical',
			'Transcription' => 'transcription',
			'Famous' => 'famous'
		],
	])

	@include('webapp.components.sorting.option', [
		'label' => 'FILTER BY ENSEMBLE',
		'type' => 'checkbox',
		'name' => 'ensemble',
		'options' => [
			'4-hands' => '4 hands',
			'6-hands' => '6 hands',
			'8-hands' => '8 hands',
			'2 pianos' => '2 pianos'
		],
	])
@endcomponent