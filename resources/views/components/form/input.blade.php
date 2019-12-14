<div class="form-group">
	@include('components.form.label')
	<input 
		class="form-control {{$classes ?? null}} {{validate($errors->$bag, $name)}}" 
		{{$required ?? 'required'}} 
		type="{{$type ?? 'text'}}" 
		@if(! empty($type) && $type == 'number')
		min="0"
		@endif
		name="{{$name}}" 
		@if(! empty($limit))
		maxlength="{{$limit}}"
		@endif
		placeholder="{{$placeholder ?? snake_str($name)}}" 
		@if(old($name))
		value="{{old($name)}}"
		@else
		value="{{$value ?? null}}"
		@endif
	>

	@include('components/form/error', ['bag' => $bag, 'field' => $name])
</div>