@php
$fields = [
	empty($studioPolicy) ? old('parent_agreement') : $studioPolicy->get('parent_agreement'),
	empty($studioPolicy) ? old('student_agreement') : $studioPolicy->get('student_agreement')
]
@endphp

@component('users.studio-policies.create.steps.step', ['title' => 'Agreements', 'loop' => $loop, 'count' => count($fields), 'isNew' => empty($studioPolicy)])
	@options([
		'label' => 'Require signature of the parent/guardian?', 
		'type' => 'radio', 
		'value' => $fields[0], 
		'options' => ['Yes' => 1, 'No' => 0], 
		'name' => 'parent_agreement', 
		'bag' => 'default', 
		'asterisk' => true])

	@options([
		'label' => 'Require signature of the student?', 
		'type' => 'radio', 
		'value' => $fields[1], 
		'options' => ['Yes' => 1, 'No' => 0], 
		'name' => 'student_agreement', 
		'bag' => 'default', 
		'asterisk' => true])
@endcomponent