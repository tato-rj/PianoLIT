@php
$fields = [
	empty($studioPolicy) ? old('lesson_fees[]') : $studioPolicy->lessonFees(),
	empty($studioPolicy) ? old('hide_fees') : $studioPolicy->get('hide_fees'),
	empty($studioPolicy) ? old('extra_fees') : $studioPolicy->get('extra_fees'),
	empty($studioPolicy) ? old('expectations') : $studioPolicy->get('expectations'),
	empty($studioPolicy) ? old('observation') : $studioPolicy->get('observation'),
]
@endphp

@component('users.studio-policies.create.steps.step', ['title' => 'Lessons & fees', 'loop' => $loop, 'count' => count($fields)])
	<div class="form-group">
		@include('components.form.label', [
			'label' => 'Choose the types of lesson you offer by specifying how much you charge'])

		@foreach(\App\StudioPolicy::durations() as $duration)
		<div class="input-group mb-2">
		  <div class="input-group-prepend">
		    <span class="input-group-text" id="addon-wrapping">{{$duration}} min</span>
		  </div>
			<input class="form-control text-right {{validate($errors->default, 'lesson_fees[]')}}"
				step="5" type="number" name="lesson_fees[]" max="500" min="0"
				value="{{$fields[0][$duration][0]}}"style="max-width: 72px">
			  <div class="input-group-append">
			  	<select class="form-control" name="lesson_duration[]" style="border-top-left-radius: 0; border-bottom-left-radius: 0;">
			  		<option value="hour" {{$fields[0][$duration][1] == 'hour' ? 'selected' : null}}>/hour</option>
			  		<option value="month" {{$fields[0][$duration][1] == 'month' ? 'selected' : null}}>/month</option>
			  	</select>
			  </div>
		</div>
		@include('components/form/error', ['bag' => 'default', 'field' => 'lesson_fees[]'])
		@endforeach
	</div>

	@options([ 
	'type' => 'checkbox', 
	'values' => $fields[1], 
	'options' => ['I charge my lesson in a different way, I would rather not show these' => 'yes'], 
	'name' => 'hide_fees', 
	'required' => 'not-required',
	'bag' => 'default'])

	@textarea([
		'label' => 'Indicate on the box below if you charge any extra fees or want to give more details on this subject:', 
		'value' => $fields[2], 
		'name' => 'extra_fees', 
		'bag' => 'default', 
		'required' => 'not-required', 
		'limit' => 280, 
		'rows' => 3])
	
	@options([
		'label' => 'Which requirements (if any) do you want to include?', 
		'type' => 'checkbox', 
		'values' => $fields[3], 
		'options' => [
			'Daily practice' => 'Daily Practice: this is the key to a successful and fulfilling learning experience.', 
			'Attendance' => 'Attendance: Regular and on-time attendance is required.', 
			'Short finger nails' => 'Short finger nails: all finger nails need to be kept short to allow for good technique.'], 
		'name' => 'expectations', 
		'required' => 'not-required',
		'bag' => 'default'])

	@options([
		'label' => 'How involved do you expect the parent/guardian to be during the lessons?', 
		'type' => 'radio', 
		'value' => $fields[4], 
		'options' => [
			'They must always be present and take notes of what we did in class' => 'Parents/guardians are required to observe every lesson and take notes. Their participation in the practice sessions at home are be very important in the student\'s development.', 
			'They can observe if they want to, but it is not required' => 'Parents/guardians are welcome to observe the lesson, but are not required to do so. The students are fully responsible for keeping track of their homework and being prepared to every class.',
			'I prefer to not have the lessons observed' => 'My recommendation for parents/guardians is not to attend the lessons, as students tend to focus and be more creative with fewer adult observers.',
			'I don\'t want to show this on the policy' => 'null'], 
		'name' => 'observation', 
		'bag' => 'default', 
		'asterisk' => true])
@endcomponent