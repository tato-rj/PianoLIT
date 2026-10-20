@php
    $inputBag = $bag ?? 'default';
    $inputErrors = $errors->getBag($inputBag);
    $oldInputValue = old($oldInputKey ?? $name);
    $inputValue = is_scalar($oldInputValue) ? $oldInputValue : ($value ?? null);
@endphp
<div class="form-group {{$grid ?? null}}">
	@include('components.form.label', ['asterisk' => $asterisk ?? null])
	<input 
		class="form-control pianolit-input {{$classes ?? null}} {{validate($inputErrors, $name)}}"
		@isset($id)
		id="{{$id}}"
		@endisset
		style="{{$styles ?? null}}" 
		{{$required ?? 'required'}} 
		@if($disabled ?? false) disabled @endif
		type="{{$type ?? 'text'}}" 
		@if(! empty($type) && $type == 'number')
		min="{{$min ?? 0}}"
		step="{{$step ?? null}}"
		@endif
		name="{{$name}}" 
		@isset($autocomplete)
		autocomplete="{{$autocomplete}}"
		@endisset
		@isset($describedBy)
		aria-describedby="{{$describedBy}}"
		@endisset
		@if($inputErrors->has($name)) aria-invalid="true" @endif
		@if($designSystemPreview ?? false) data-ds-preview @endif
		@if(! empty($limit))
		maxlength="{{$limit}}"
		max="{{$limit}}"
		@endif
		placeholder="{{$placeholder ?? ucfirst(snake_str($name, true))}}" 
		value="{{$inputValue}}"
	>

	@include('components.form.error', ['bag' => $inputBag, 'field' => $name])
</div>
