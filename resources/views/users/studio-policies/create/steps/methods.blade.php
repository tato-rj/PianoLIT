@php
$fields = [
	empty($studioPolicy) ? old('methods') : $studioPolicy->get('methods'),
	empty($studioPolicy) ? old('other_methods') : $studioPolicy->get('other_methods'),
];
@endphp

@component('users.studio-policies.create.steps.step', ['title' => 'Methods', 'loop' => $loop, 'count' => count($fields), 'isNew' => empty($studioPolicy)])

	@options([
		'label' => 'Do you teach with in any specific method? Select any that applies', 
		'type' => 'checkbox', 
		'classes' => 'd-block',
		'values' => $fields[0], 
		'options' => ['Suzuki Method' => 'Suzuki Method', 'Taubman Aproach' => 'Taubman Aproach', 'Alfred Piano Method' => 'Alfred Piano Series', 'Faber & Faber Series' => 'Faber & Faber Method', 'Safari Piano Method' => 'Safari Piano Method', 'Bastien Piano Series' => 'Bastien Piano Series'], 
		'name' => 'methods', 
		'required' => 'not-required',
		'bag' => 'default'])

	@textarea([
		'label' => 'If you have any specific methods or practices you use, please indicate that in the space below', 
		'value' => $fields[1], 
		'name' => 'other_methods', 
		'bag' => 'default', 
		'required' => 'not-required', 
		'limit' => 280, 
		'rows' => 2])
@endcomponent