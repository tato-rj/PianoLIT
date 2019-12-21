@php
$fields = [
	empty($studioPolicy) ? old('materials') : $studioPolicy->get('materials'),
	empty($studioPolicy) ? old('other_materials') : $studioPolicy->get('other_materials'),
	empty($studioPolicy) ? old('materials_buyer') : $studioPolicy->get('materials_buyer'),
]
@endphp

@component('users.studio-policies.create.steps.step', ['title' => 'Materials', 'loop' => $loop, 'count' => count($fields), 'isNew' => empty($studioPolicy)])

	@options([
		'label' => 'Select all (if any) types of materials you use', 
		'type' => 'checkbox', 
		'classes' => 'd-block',
		'values' => $fields[0], 
		'options' => ['Theory books' => 'theory books', 'Technique books' => 'technique books', 'Repertoire books' => 'repertoire books', 'Sight reading books' => 'sight reading books', 'Assignment notebooks' => 'assignment notebooks'], 
		'name' => 'materials', 
		'required' => 'not-required',
		'bag' => 'default'])

	@textarea([
		'label' => 'If you use other types of materials, list them below', 
		'value' => $fields[1], 
		'name' => 'other_materials', 
		'bag' => 'default', 
		'required' => 'not-required', 
		'limit' => 280, 
		'rows' => 2])

	@options([
		'label' => 'Do you buy the materials yourself?', 
		'type' => 'radio', 
		'value' => $fields[2], 
		'options' => ['Yes, I buy the books and bill the student' => 1, 'No, students buy their own books' => 0], 
		'name' => 'materials_buyer', 
		'bag' => 'default', 
		'asterisk' => true])

@endcomponent