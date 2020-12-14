<div class="form-group {{$grid ?? null}}">
	@include('components.form.label', ['asterisk' => $asterisk ?? null])

	<div class="input-group">
		@isset($value)
	    <div class="input-group-prepend">
	      <a class="input-group-text no-underline {{$value ? 'text-success' : 'text-muted opacity-4'}}" href="{{storage($value)}}" target="_blank">@fa(['icon' => 'file-alt', 'mr' => 0])</a>
	    </div>
	    @endisset
		<div class="custom-file">
			<input type="file" {{$required ?? 'required'}}  class="custom-file-input  {{$classes ?? null}} {{validate($errors->$bag, $name)}}" name="{{$name}}" id="{{$name}}-file">
			<label class="custom-file-label truncate" for="{{$name}}-file">{{$placeholder ?? ucfirst(snake_str($name, true))}}</label>
		</div>
	</div>

	@include('components/form/error', ['bag' => $bag, 'field' => $name])
</div>
