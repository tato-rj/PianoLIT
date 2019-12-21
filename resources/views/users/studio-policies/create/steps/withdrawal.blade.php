@php
$fields = [
	empty($studioPolicy) ? old('withdrawal_fee') : $studioPolicy->get('withdrawal_fee'),
	empty($studioPolicy) ? old('withdrawal_month') : $studioPolicy->get('withdrawal_month'),
];
@endphp

@component('users.studio-policies.create.steps.step', ['title' => 'Withdrawal', 'loop' => $loop, 'count' => count($fields), 'isNew' => empty($studioPolicy)])

	<div class="form-group">
		@include('components.form.label', [
			'label' => 'If you charge a fee when a student withdraws from your studio, just indicate on the space below:'])
		<div class="input-group mb-2">
		  <div class="input-group-prepend">
		    <span class="input-group-text bg-light" id="addon-wrapping">$</span>
		  </div>
			<input class="form-control text-right {{validate($errors->default, 'lesson_fees[]')}}"
				step="5" type="number" name="withdrawal_fee" max="100" min="0"
				value="{{$fields[0]}}"style="max-width: 72px">
		</div>
		@include('components/form/error', ['bag' => 'default', 'field' => 'lesson_fees[]'])
	</div>

	<div class="form-group">
		@include('components.form.label', ['label' => 'If you do charge a withdrawal fee, is there a specific month you allow for withdrawals without fee? If yes, just choose the month below:'])
		<select class="form-control" name="withdrawal_month">
			<option selected disabled>Select a month</option>
			@foreach(\Carbon\CarbonPeriod::create('2000-01-01', '1 month', '2000-12-31') as $date)
			<option value="{{$date->format('m')}}" {{$fields[1] == $date->format('m') ? 'selected' : null}}>{{$date->format('F')}}</option>
			@endforeach
			<option value="0">No, there are no such months</option>
		</select>
	</div> 

{{-- 	@options([
		'label' => '',
		'type' => 'radio', 
		'value' => $fields[1], 
		'options' => ['Yes' => 'make up', 'I offer a refund' => 'refund', 'I offer both' => 'both', 'I dont\'t offer either one' => 'none'], 
		'name' => 'makeup_policy',
		'bag' => 'default', 
		'asterisk' => true]) --}}

{{-- 	<div class="form-group"> 
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
	</div> --}}

@endcomponent