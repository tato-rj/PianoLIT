<div class="form-group {{$grid ?? null}}">
	@include('components.form.label', ['asterisk' => $asterisk ?? null])
	<select class="form-control {{$classes ?? null}} {{validate($errors->$bag, $name)}}" 
		@isset($id)
		id="{{$id}}"
		@endisset
		style="{{$styles ?? null}}" 
		@if($designSystemPreview ?? false) data-ds-preview @endif
		{{$required ?? 'required'}} 
		name="{{$name}}">

		@isset($placeholder)
		<option selected disabled>{{$placeholder}}</option>
		@endisset

		@isset($choices)
		@php($selectedValue = is_scalar(old($name)) ? old($name) : ($value ?? null))
		@foreach($choices as $optionValue => $optionLabel)
		<option value="{{$optionValue}}" @if((string) $selectedValue === (string) $optionValue) selected @endif>{{$optionLabel}}</option>
		@endforeach
		@else
		@isset($options)
		@foreach($options as $label => $value)
		<option value="{{$value}}" 
		@isset($select)
		{{$select == $value ? 'selected' : null}}
		@endisset
		{{old($name) == $value ? 'selected' : ''}}>{{is_string($label) ? $label : ucfirst($value)}}</option>
		@endforeach
		@endisset
		
		@isset($optGroups)
		@foreach($optGroups as $group => $options)
		<optgroup label="{{ucfirst($group)}}">
			@foreach($options as $label => $value)
			<option value="{{$value}}" 
			@isset($select)
			{{$select == $value ? 'selected' : null}} 
			@endisset
			{{old($name) == $value ? 'selected' : ''}}>{{is_string($label) ? $label : ucfirst($value)}}</option>
			@endforeach
		</optgroup>
		@endforeach
		@endisset
		@endisset
	</select>
	@include('components.form.error', ['bag' => $bag, 'field' => $name])
</div>
