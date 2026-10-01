<div class="{{$nogutters ?? 'form-group'}}">
	@include('components.form.label', ['asterisk' => $asterisk ?? null])
	<div class="ms-2">
		@foreach($options as $label => $option)
		<div class="form-check mb-2 custom-{{$type}} {{$type == 'checkbox' ? 'options-required' : null}} form-check-inline {{$classes ?? null}}">
		  <input type="{{$type}}" id="{{"$name.$loop->iteration"}}" value="{{$option}}" name="{{$type == 'checkbox' ? $name.'[]' : $name}}"
		  @if($type == 'checkbox')
		  @if(!empty($values)) {{ in_array($option, $values) ? 'checked' : null}} @endif
		  @else
		  @if(!empty($value) || $value == 0) {{! is_null($value) && $value == $option ? 'checked' : null}} @endif
		  @endif
		  class="form-check-input {{validate($errors->$bag, $name)}}" {{$required ?? 'required'}}>
		  <label class="form-check-label" for="{{"$name.$loop->iteration"}}">{{ $label }}</label>
		</div>
		@endforeach
	</div>

	@include('components/form/error', ['bag' => $bag, 'field' => $name])
</div>