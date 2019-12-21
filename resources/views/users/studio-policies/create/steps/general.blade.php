@php
$fields = [
	empty($studioPolicy) ? old('nickname') : $studioPolicy->get('nickname'),
	empty($studioPolicy) ? old('name') : $studioPolicy->get('name'),
	empty($studioPolicy) ? old('welcome') : $studioPolicy->get('welcome'),
	empty($studioPolicy) ? old('start_year') : $studioPolicy->get('start_year'),
	empty($studioPolicy) ? old('end_year') : $studioPolicy->get('end_year'),
	empty($studioPolicy) ? old('start_month') : $studioPolicy->get('start_month'),
	empty($studioPolicy) ? old('end_month') : $studioPolicy->get('end_month'),
	empty($studioPolicy) ? old('contact_methods') : $studioPolicy->get('contact_methods')
];
@endphp

@component('users.studio-policies.create.steps.step', ['title' => 'General information', 'loop' => $loop, 'count' => count($fields), 'isNew' => empty($studioPolicy)])	
	@input([
		'label' => 'Name of this policy (only you will see this)', 
		'value' => $fields[0], 
		'placeholder' => 'Something that describes this policy, ex: General Policy, School Policy, etc...',
		'name' => 'nickname', 
		'bag' => 'default', 
		'asterisk' => true])

	@input([
		'label' => 'Your name (or your studio)', 
		'value' => $fields[1], 
		'name' => 'name', 
		'bag' => 'default', 
		'asterisk' => true])

	@textarea([
		'label' => 'If you want to have a welcome message at the beginning of the policy, write it in the box below:', 
		'value' => $fields[2], 
		'name' => 'welcome', 
		'bag' => 'default', 
		'required' => 'not-required', 
		'limit' => 280, 
		'rows' => 2])

	<div class="form-group form-row"> 
		<div class="col">
			@include('components.form.label', [
				'label' => 'This policy is valid from', 
				'asterisk' => true])
			<select class="form-control" name="start_year" required>
				<option selected disabled>Select a year</option>
				<option value="{{now()->year - 1}}" {{$fields[3] == now()->year - 1 ? 'selected' : null}}>{{now()->year - 1}}</option>
				<option value="{{now()->year}}" {{$fields[3] == now()->year ? 'selected' : null}}>{{now()->year}}</option>
				<option value="{{now()->year + 1}}" {{$fields[3] == now()->year + 1 ? 'selected' : null}}>{{now()->year + 1}}</option>
			</select>
		</div> 
		<div class="col">
			@include('components.form.label', [
				'label' => 'This policy is valid until', 
				'asterisk' => true])
			<select class="form-control" name="end_year" required>
				<option selected disabled>Select a year</option>
				<option value="{{now()->year - 1}}" {{$fields[4] == now()->year - 1 ? 'selected' : null}}>{{now()->year - 1}}</option>
				<option value="{{now()->year}}" {{$fields[4] == now()->year ? 'selected' : null}}>{{now()->year}}</option>
				<option value="{{now()->year + 1}}" {{$fields[4] == now()->year + 1 ? 'selected' : null}}>{{now()->year + 1}}</option>
			</select>
		</div>
	</div>

	<div class="form-group form-row"> 
		<div class="col">
			@include('components.form.label', [
				'label' => 'The month you start teaching', 
				'asterisk' => true])
			<select class="form-control" name="start_month" required>
				<option selected disabled>Select a month</option>
				@foreach(\Carbon\CarbonPeriod::create('2000-01-01', '1 month', '2000-12-31') as $date)
				<option value="{{$date->format('m')}}" {{$fields[5] == $date->format('m') ? 'selected' : null}}>{{$date->format('F')}}</option>
				@endforeach
			</select>
		</div> 
		<div class="col">
			@include('components.form.label', [
				'label' => 'The last month before summer', 
				'asterisk' => true])
			<select class="form-control" name="end_month" required>
				<option selected disabled>Select a month</option>
				@foreach(\Carbon\CarbonPeriod::create('2000-01-01', '1 month', '2000-12-31') as $date)
				<option value="{{$date->format('m')}}" {{$fields[6] == $date->format('m') ? 'selected' : null}}>{{$date->format('F')}}</option>
				@endforeach
			</select>
		</div>
	</div>

	@options([
		'label' => 'Select the best ways to contact you (if relevant)', 
		'type' => 'checkbox', 
		'values' => $fields[7], 
		'options' => ['Phone call' => 'phone', 'Text message' => 'text', 'Email' => 'email'], 
		'name' => 'contact_methods',
		'required' => 'not-required',
		'bag' => 'default'])
@endcomponent