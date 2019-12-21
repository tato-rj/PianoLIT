@php
$fields = [
	empty($studioPolicy) ? old('payment_due') : $studioPolicy->get('payment_due'),
	empty($studioPolicy) ? old('payment_methods') : $studioPolicy->get('payment_methods'),
	empty($studioPolicy) ? old('payment_note') : $studioPolicy->get('payment_note'),
];
@endphp

@component('users.studio-policies.create.steps.step', ['title' => 'Payments', 'loop' => $loop, 'count' => count($fields), 'isNew' => empty($studioPolicy)])

	@options([
		'label' => 'When do you accept payments? Select any that applies',
		'type' => 'checkbox',
		'values' => $fields[0], 
		'options' => ['At each lesson' => 'at each lesson', 'At the first week of the month' => 'at the first week of the month', 'Every two weeks' => 'at every two weeks'], 
		'name' => 'payment_due',
		'bag' => 'default',
		'required' => 'not-required'])

	@options([
		'label' => 'Which payment methods do you use? Select any that applies',
		'type' => 'checkbox',
		'classes' => 'd-block', 
		'values' => $fields[1], 
		'options' => ['Cash' => 'Cash', 'Check' => 'Check', 'Bank transfer' => 'Bank transfer', 'Apple pay' => 'Apple pay', 'Venmo' => 'Venmo', 'PayPal' => 'PayPal'], 
		'name' => 'payment_methods',
		'bag' => 'default',
		'required' => 'not-required'])

	@textarea([
		'label' => 'If you have any specific considerations regarding payments, indicate in the box below', 
		'value' => $fields[2], 
		'name' => 'payment_note', 
		'bag' => 'default', 
		'required' => 'not-required', 
		'limit' => 280, 
		'rows' => 3])
@endcomponent