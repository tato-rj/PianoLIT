@php
$fields = [
	empty($studioPolicy) ? old('vacation_weeks') : $studioPolicy->get('vacation_weeks'),
	empty($studioPolicy) ? old('makeup_weeks') : $studioPolicy->get('makeup_weeks'),
	empty($studioPolicy) ? old('provide_calendar') : $studioPolicy->get('provide_calendar'),
	empty($studioPolicy) ? old('summer_teaching') : $studioPolicy->get('summer_teaching'),
	empty($studioPolicy) ? old('summer_policy') : $studioPolicy->get('summer_policy'),
];
@endphp

@component('users.studio-policies.create.steps.step', ['title' => 'Scheduling', 'loop' => $loop, 'count' => count($fields)])

	<div class="form-row"> 
		<div class="col">
			@input([
				'value' => $fields[0],
				'name' => 'vacation_weeks', 
				'label' => 'Number of vacation weeks per year', 
				'bag' => 'default', 
				'type' => 'number', 
				'limit' => 24, 
				'asterisk' => true])
		</div>
		<div class="col">
			@input([
				'value' => $fields[1],
				'name' => 'makeup_weeks', 
				'label' => 'Number of make-up weeks per year', 
				'bag' => 'default', 
				'type' => 'number', 
				'limit' => 24,
				'asterisk' => true])
		</div>
	</div>

	@options([
		'label' => 'Do you provide a calendar for the year?', 
		'type' => 'radio', 
		'value' => $fields[2], 
		'options' => ['Yes' => 1, 'No' => 0], 
		'name' => 'provide_calendar', 
		'bag' => 'default', 
		'asterisk' => true])

	@options([
		'label' => 'Do you teach during the summer?', 
		'type' => 'radio', 
		'value' => $fields[3], 
		'options' => ['Yes' => 1, 'No' => 0], 
		'name' => 'summer_teaching', 
		'bag' => 'default', 
		'asterisk' => true])

	@textarea([
		'label' => 'If so, describe below how you schedule and charge for those lessons', 
		'value' => $fields[4], 
		'name' => 'summer_policy', 
		'bag' => 'default', 
		'required' => 'not-required', 
		'limit' => 480, 
		'rows' => 3])
@endcomponent