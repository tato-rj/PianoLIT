@component('studio-policies.create.steps.step', ['title' => 'General information', 'loop' => $loop])

	@include('components.form.input', ['value' => auth()->user()->full_name, 'name' => 'name', 'label' => 'Your name (or your studio)', 'bag' => 'default'])

	<div class="form-group form-row"> 
		<div class="col">
			<label class="text-muted">
				<small><strong>This policy is valid from</strong></small>
			</label>
			<select class="form-control" name="start_year">
				<option selected disabled>Select a year</option>
				<option value="{{now()->year - 1}}" {{old('start_year') == now()->year - 1 ? 'selected' : null}}>{{now()->year - 1}}</option>
				<option value="{{now()->year}}" {{old('start_year') == now()->year ? 'selected' : null}}>{{now()->year}}</option>
				<option value="{{now()->year + 1}}" {{old('start_year') == now()->year + 1 ? 'selected' : null}}>{{now()->year + 1}}</option>
			</select>
		</div> 
		<div class="col">
			<label class="text-muted">
				<small><strong>This policy is valid until</strong></small>
			</label>
			<select class="form-control" name="end_year">
				<option selected disabled>Select a year</option>
				<option value="{{now()->year - 1}}" {{old('start_year') == now()->year - 1 ? 'selected' : null}}>{{now()->year - 1}}</option>
				<option value="{{now()->year}}" {{old('start_year') == now()->year ? 'selected' : null}}>{{now()->year}}</option>
				<option value="{{now()->year + 1}}" {{old('start_year') == now()->year + 1 ? 'selected' : null}}>{{now()->year + 1}}</option>
			</select>
		</div>
	</div>

	<div class="form-group form-row"> 
		<div class="col">
			<label class="text-muted">
				<small><strong>The month you start teaching</strong></small>
			</label>
			<select class="form-control" name="start_month">
				<option selected disabled>Select a month</option>
				@foreach(\Carbon\CarbonPeriod::create('2000-01-01', '1 month', '2000-12-31') as $date)
				<option value="{{$date->format('m')}}" {{old('start_month') == $date->format('m') ? 'selected' : null}}>{{$date->format('F')}}</option>
				@endforeach
			</select>
		</div> 
		<div class="col">
			<label class="text-muted">
				<small><strong>The last month before summer</strong></small>
			</label>
			<select class="form-control" name="end_month">
				<option selected disabled>Select a month</option>
				@foreach(\Carbon\CarbonPeriod::create('2000-01-01', '1 month', '2000-12-31') as $date)
				<option value="{{$date->format('m')}}" {{old('end_month') == $date->format('m') ? 'selected' : null}}>{{$date->format('F')}}</option>
				@endforeach
			</select>
		</div>
	</div>

@endcomponent