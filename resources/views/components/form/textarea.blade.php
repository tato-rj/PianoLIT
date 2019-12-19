<div class="form-group">
	@include('components.form.label', ['asterisk' => $asterisk ?? null])
	<textarea 
		class="form-control {{$classes ?? null}} {{validate($errors->$bag, $name)}}" 
		{{$required ?? 'required'}}  
		name="{{$name}}" 
		rows="{{$rows ?? 4}}" 
		@if(! empty($limit))
		maxlength="{{$limit}}"
		@endif
		placeholder="{{$placeholder ?? snake_str($name, true)}} {{! empty($limit) ? '(limit of ' . $limit . ' characters)' : null}}">@if(old($name)){{old($name)}}@else{{$value ?? null}}@endif</textarea>

	@include('components/form/error', ['bag' => $bag, 'field' => $name])
</div>