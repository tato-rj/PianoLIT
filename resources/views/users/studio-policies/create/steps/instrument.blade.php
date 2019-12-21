@php
$fields = [
	empty($studioPolicy) ? old('instrument_type') : $studioPolicy->get('instrument_type'),
	empty($studioPolicy) ? old('instrument_accessories') : $studioPolicy->get('instrument_accessories'),
];
@endphp

@component('users.studio-policies.create.steps.step', ['title' => 'Instrument', 'loop' => $loop, 'count' => count($fields), 'isNew' => empty($studioPolicy)])

	@options([
		'label' => 'Which types of instruments (if any) do you recommend for your students?',
		'type' => 'checkbox',
		'values' => $fields[0], 
		'options' => ['An acoustic piano' => 'acoustic_piano', 'A digital piano' => 'digital_piano'], 
		'name' => 'instrument_type',
		'bag' => 'default', 
		'required' => 'not-required'])

	@options([
		'label' => 'Which accessories do you provide (if any) during the lesson?',
		'type' => 'checkbox',
		'classes' => 'd-block', 
		'values' => $fields[1], 
		'options' => ['Footrest' => 'footrest', 'Cushion' => 'cushion'], 
		'name' => 'instrument_accessories',
		'bag' => 'default',
		'required' => 'not-required'])

@endcomponent