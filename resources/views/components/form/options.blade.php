<div class="form-group">
	@include('components.form.label', ['asterisk' => $asterisk ?? null])
	<div class="ml-2">
		@foreach($options as $label => $option)
		<div class="custom-control mb-2 custom-{{$type}} {{$type == 'checkbox' ? 'options-required' : null}} custom-control-inline {{$classes ?? null}}">
		  <input type="{{$type}}" id="{{"$name.$loop->iteration"}}" value="{{$option}}" name="{{$type == 'checkbox' ? $name.'[]' : $name}}"
		  @if($type == 'checkbox')  
		  @if(!empty($values)) {{ in_array($option, $values) ? 'checked' : null}} @endif
		  @else
		  @if(!empty($value) || $value == 0) {{! is_null($value) && $value == $option ? 'checked' : null}} @endif
		  @endif
		  class="custom-control-input {{validate($errors->$bag, $name)}}" {{$required ?? 'required'}}>
		  <label class="custom-control-label" for="{{"$name.$loop->iteration"}}">{{ $label }}</label>
		</div>
		@endforeach
	</div>

	@include('components/form/error', ['bag' => $bag, 'field' => $name])
</div>