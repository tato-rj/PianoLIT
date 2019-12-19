@php
$fields = [
	empty($studioPolicy) ? old('absence_notice') : $studioPolicy->get('absence_notice'),
	empty($studioPolicy) ? old('makeup_policy') : $studioPolicy->get('makeup_policy'),
	empty($studioPolicy) ? old('sickness_policy') : $studioPolicy->get('sickness_policy')
];
@endphp

@component('users.studio-policies.create.steps.step', ['title' => 'Make up lessons', 'loop' => $loop, 'count' => count($fields)])
	<div class="form-group"> 
		@include('components.form.label', [
			'label' => 'You accept a cancellation up until', 
			'asterisk' => true])
		<select class="form-control" name="absence_notice" required>
			<option disabled>Select an option</option>
			<option value="24" {{$fields[0] == 24 ? 'selected' : null}}>24hs in advance</option>
			<option value="48" {{$fields[0] == 48 ? 'selected' : null}}>48hs in advance</option>
			<option value="72" {{$fields[0] == 72 + 1 ? 'selected' : null}}>72hs in advance</option>
			<option value="always" {{$fields[0] == 'always' ? 'selected' : null}}>I accept cancellations at any time</option>
			<option value="never" {{$fields[0] == 'never' ? 'selected' : null}}>I don't accept cancellations</option>
		</select>
	</div>

	@options([
		'label' => 'If you accept cancellations, what do you do in such cases?', 
		'classes' => 'd-block',
		'type' => 'radio', 
		'value' => $fields[1], 
		'options' => ['I offer a make up lesson' => 'make up', 'I offer a refund' => 'refund', 'I offer both' => 'both', 'I dont\'t offer either one' => 'none'], 
		'name' => 'makeup_policy',
		'bag' => 'default', 
		'asterisk' => true])

	@options([
		'label' => 'Do you specifically ask students not to come to lesson if they are sick?', 
		'classes' => 'd-block',
		'type' => 'radio', 
		'value' => $fields[2], 
		'options' => ['Yes and we will make up the lesson' => 'make up', 'Yes but I don\'t offer a make up' => 'no make up', 'No, it is up to them to make that decision' => 'null'], 
		'name' => 'sickness_policy',
		'bag' => 'default', 
		'asterisk' => true])
@endcomponent