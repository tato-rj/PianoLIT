@php
$fields = [
	'per_lesson_fees' => empty($studioPolicy) ? old('per_lesson_fees[]') : $studioPolicy->feesPer('lesson'),
	'per_month_fees' => empty($studioPolicy) ? old('per_month_fees[]') : $studioPolicy->feesPer('month'),
	'hide_fees' => empty($studioPolicy) ? old('hide_fees') : $studioPolicy->get('hide_fees'),
	'extra_fees' => empty($studioPolicy) ? old('extra_fees') : $studioPolicy->get('extra_fees'),
	'observation' => empty($studioPolicy) ? old('observation') : $studioPolicy->get('observation'),
]
@endphp

@component('users.studio-policies.create.steps.step', ['title' => 'Lessons & fees', 'loop' => $loop, 'count' => count($fields), 'isNew' => empty($studioPolicy)])
	<div class="form-group">
		@include('components.form.label', [
			'label' => 'Choose the types of lessons you offer (if relevant) by specifying how much you charge'])
	
		<div class="d-flex">
		@foreach(\App\StudioPolicy::durations() as $duration)
			<div class="m-2 border rounded">
				<div class="mb-2 alert-grey rounded-top px-2 py-1"><strong><i class="fas fa-stopwatch mr-2"></i>{{ucfirst($duration)}} Lessons</strong></div>
				<div class="">
					@include('users.studio-policies.create.steps.components.fee', ['period' => 'lesson'])
					@include('users.studio-policies.create.steps.components.fee', ['period' => 'month'])
				</div>
			</div>
			@include('components/form/error', ['bag' => 'default', 'field' => 'lesson_fees[]'])
			@endforeach
		</div>
	</div>

	@textarea([
		'label' => 'If you charge your lessons in a different way or just want to give more details, indicate that on the box below:', 
		'value' => $fields['extra_fees'], 
		'name' => 'extra_fees', 
		'bag' => 'default', 
		'required' => 'not-required', 
		'limit' => 280, 
		'rows' => 3])

	@options([
		'label' => 'How involved do you expect the parent/guardian of a young student to be during the lessons?', 
		'type' => 'radio', 
		'value' => $fields['observation'], 
		'options' => [
			'They must always be present and take notes of what we did in class' => 'Parents/guardians are required to observe every lesson and take notes. Their participation in the practice sessions at home is very important in the student\'s development.', 
			'They can observe if they want to, but it is not required' => 'Parents/guardians are welcome to observe the lessons, but are not required to do so. Students are fully responsible for keeping track of their homework and being prepared for every class.',
			'I prefer to not have the lessons observed' => 'My recommendation for parents/guardians is not to attend the lessons, as students tend to focus and be more creative with fewer adult observers.',
			'I don\'t want to show this on the policy' => 'null'], 
		'name' => 'observation', 
		'bag' => 'default', 
		'asterisk' => true])
@endcomponent