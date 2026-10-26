<label class="switch cursor-pointer">
	{{-- Used for sorting --}}
	<span class="position-absolute invisible">{{$toggle ? 1 : 0}}</span>

	<input class="{{($autoToggle ?? false) ? 'status-toggle' : null}}" type="checkbox"
		@isset($route)
	 	data-url="{{$route}}"
	 	@endisset

		@isset($name)
	 	name="{{$name}}"
	 	@endisset

		@isset($inputValue)
		value="{{$inputValue}}"
		@endisset

		@isset($ariaLabel)
		role="switch" aria-label="{{$ariaLabel}}"
		@endisset

		@isset($describedBy)
		aria-describedby="{{$describedBy}}"
		@endisset

		@if($submitOnChange ?? false)
		onchange="this.form.requestSubmit()"
		@endif

	 	{{$toggle ? 'checked' : null}}>
	
	<span class="slider round" aria-hidden="true" @if($designSystemPreview ?? false) data-ds-preview @endif></span>
</label>
