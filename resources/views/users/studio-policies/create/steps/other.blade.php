@php
$fields = [
	empty($studioPolicy) ? old('other_considerations') : $studioPolicy->get('other_considerations'),
];
@endphp

@component('users.studio-policies.create.steps.step', ['title' => 'Other considerations', 'loop' => $loop, 'count' => count($fields), 'isNew' => empty($studioPolicy)])

	@textarea([
		'label' => 'Is there anything you would like to add to this policy that was not covered? Use this space to add any other considerations you find relevant to your specific case:', 
		'value' => $fields[0], 
		'name' => 'other_considerations', 
		'bag' => 'default', 
		'required' => 'not-required', 
		'limit' => 680, 
		'rows' => 5])

@endcomponent