@php
    $oldTextareaValue = old($oldInputKey ?? $name);
    $textareaValue = is_scalar($oldTextareaValue) ? $oldTextareaValue : ($value ?? null);
@endphp
<div class="form-group">
	@include('components.form.label', ['asterisk' => $asterisk ?? null])
	<textarea 
		class="form-control pianolit-input {{$classes ?? null}} {{validate($errors->$bag, $name)}}"
		@isset($id) id="{{ $id }}" @endisset
		@if(($textImprover ?? true) === false) data-text-improver="off" @endif
		{{$required ?? 'required'}}  
		name="{{$name}}" 
		rows="{{$rows ?? 4}}" 
		@if(! empty($limit))
		maxlength="{{$limit}}"
		@endif
		placeholder="{{$placeholder ?? snake_str($name, true)}} {{! empty($limit) ? '(limit of ' . $limit . ' characters)' : null}}">{{ $textareaValue }}</textarea>

	@include('components/form/error', ['bag' => $bag, 'field' => $name])
</div>
