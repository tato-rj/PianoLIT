@php
$fields = [
	empty($studioPolicy) ? old('group_classes') : $studioPolicy->get('group_classes'),
	empty($studioPolicy) ? old('recitals') : $studioPolicy->get('recitals'),
	empty($studioPolicy) ? old('group_classes_description') : $studioPolicy->get('group_classes_description'),
	empty($studioPolicy) ? old('recitals_description') : $studioPolicy->get('recitals_description'),
	empty($studioPolicy) ? old('extra_performances') : $studioPolicy->get('extra_performances')
];
@endphp

@component('users.studio-policies.create.steps.step', ['title' => 'Performances', 'loop' => $loop, 'count' => count($fields)])

	<div class="form-row"> 
		<div class="col">
		@input([
			'label' => 'Number of group classes per year', 
			'value' => $fields[0],
			'name' => 'group_classes', 
			'bag' => 'default', 
			'type' => 'number', 
			'limit' => 24, 
			'asterisk' => true])
		</div>
		<div class="col">
			@input([
				'label' => 'Number of recitals per year', 
				'value' => $fields[1],
				'name' => 'recitals', 
				'bag' => 'default', 
				'type' => 'number', 
				'limit' => 24, 
				'asterisk' => true])
		</div>
	</div>

	@textarea([
		'label' => 'Use this are write a short description of the group classes, what they are for, etc...', 
		'value' => $fields[2], 
		'name' => 'group_classes_description', 
		'bag' => 'default', 
		'required' => 'not-required', 
		'limit' => 280, 
		'rows' => 3])

	@textarea([
		'label' => 'Use this are write a short description of the recitals, their locations, dress code, etc...', 
		'value' => $fields[3], 
		'name' => 'recitals_description', 
		'bag' => 'default', 
		'required' => 'not-required', 
		'limit' => 280, 
		'rows' => 3])

	@textarea([
		'label' => 'Do you encourage or direct students to other events such as festivals, competitions or conferences? If relevant, use the space below to indicate that.', 
		'value' => $fields[4], 
		'name' => 'extra_performances', 
		'bag' => 'default', 
		'required' => 'not-required', 
		'limit' => 560, 
		'rows' => 4])

@endcomponent