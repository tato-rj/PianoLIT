<div class="form-group">
	<textarea class="rounded {{validate($errors->$bag, $name)}}" id="editor" name="{{$name}}" rows="32">{{$value ?? old($name)}}</textarea>

	@include('components/form/error', ['bag' => $bag, 'field' => $name])
</div>