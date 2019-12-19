@php
$fields = [
	empty($studioPolicy) ? old('name') : $studioPolicy->get('name'),
	empty($studioPolicy) ? old('start_year') : $studioPolicy->get('start_year'),
	empty($studioPolicy) ? old('end_year') : $studioPolicy->get('end_year'),
	empty($studioPolicy) ? old('start_month') : $studioPolicy->get('start_month'),
	empty($studioPolicy) ? old('end_month') : $studioPolicy->get('end_month'),
	empty($studioPolicy) ? old('contact_methods') : $studioPolicy->get('contact_methods')
];
@endphp

@component('users.studio-policies.create.steps.step', ['title' => 'General information', 'loop' => $loop, 'count' => count($fields)])	
	@input([
		'label' => 'Your name (or your studio)', 
		'value' => $fields[0], 
		'name' => 'name', 
		'bag' => 'default', 
		'asterisk' => true])
	
	<div class="form-group form-row"> 
		<div class="col">
			@include('components.form.label', [
				'label' => 'This policy is valid from', 
				'asterisk' => true])
			<select class="form-control" name="start_year" required>
				<option selected disabled>Select a year</option>
				<option value="{{now()->year - 1}}" {{$fields[1] == now()->year - 1 ? 'selected' : null}}>{{now()->year - 1}}</option>
				<option value="{{now()->year}}" {{$fields[1] == now()->year ? 'selected' : null}}>{{now()->year}}</option>
				<option value="{{now()->year + 1}}" {{$fields[1] == now()->year + 1 ? 'selected' : null}}>{{now()->year + 1}}</option>
			</select>
		</div> 
		<div class="col">
			@include('components.form.label', [
				'label' => 'This policy is valid until', 
				'asterisk' => true])
			<select class="form-control" name="end_year" required>
				<option selected disabled>Select a year</option>
				<option value="{{now()->year - 1}}" {{$fields[2] == now()->year - 1 ? 'selected' : null}}>{{now()->year - 1}}</option>
				<option value="{{now()->year}}" {{$fields[2] == now()->year ? 'selected' : null}}>{{now()->year}}</option>
				<option value="{{now()->year + 1}}" {{$fields[2] == now()->year + 1 ? 'selected' : null}}>{{now()->year + 1}}</option>
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
				<option value="{{$date->format('m')}}" {{$fields[3] == $date->format('m') ? 'selected' : null}}>{{$date->format('F')}}</option>
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
				<option value="{{$date->format('m')}}" {{$fields[4] == $date->format('m') ? 'selected' : null}}>{{$date->format('F')}}</option>
				@endforeach
			</select>
		</div>
	</div>

	@options([
		'label' => 'Select the best ways to contact you (if relevant)', 
		'type' => 'checkbox', 
		'values' => $fields[5], 
		'options' => ['Phone call' => 'phone', 'Text message' => 'text', 'Email' => 'email'], 
		'name' => 'contact_methods',
		'required' => 'not-required',
		'bag' => 'default'])
@endcomponent