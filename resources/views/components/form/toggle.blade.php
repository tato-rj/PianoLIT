<label class="switch cursor-pointer">
	{{-- Used for sorting --}}
	<span class="position-absolute invisible">{{$toggle ? 1 : 0}}</span>

	<input class="{{$autoToggle ? 'status-toggle' : null}}" type="checkbox"
		@isset($route)
	 	data-url="{{$route}}"
	 	@endisset

		@isset($name)
	 	name="{{$name}}"
	 	@endisset

	 	{{$toggle ? 'checked' : null}}>
	
	<span class="slider round"></span>
</label>