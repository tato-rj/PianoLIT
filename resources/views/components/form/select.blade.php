<div class="form-group {{$grid ?? null}}">
	@include('components.form.label', ['asterisk' => $asterisk ?? null])
	<select class="form-control {{$classes ?? null}} {{validate($errors->$bag, $name)}}" 
		@isset($id)
		id="{{$id}}"
		@endisset
		style="{{$styles ?? null}}" 
		{{$required ?? 'required'}} 
		name="{{$name}}">

		@isset($placeholder)
		<option selected disabled>{{$placeholder}}</option>
		@endisset

		@isset($options)
		@foreach($options as $label => $value)
		<option value="{{$value}}" {{old($name) == $value ? 'selected' : ''}}>{{is_string($label) ? $label : ucfirst($value)}}</option>
		@endforeach
		@endisset
		
		@isset($optGroups)
		@foreach($optGroups as $group => $options)
		<optgroup label="{{ucfirst($group)}}">
			@foreach($options as $label => $value)
			<option value="{{$value}}" {{old($name) == $value ? 'selected' : ''}}>{{is_string($label) ? $label : ucfirst($value)}}</option>
			@endforeach
		</optgroup>
		@endforeach
		@endisset
	</select>
	@include('components.form.error', ['bag' => $bag, 'field' => $name])
</div>