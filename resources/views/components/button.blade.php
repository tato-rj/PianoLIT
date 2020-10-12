{{-- @if(!empty($href)) --}}
<{{empty($href) ? 'button' : 'a'}}
	@isset($href)
	href="{{$href}}" 
	@endisset

	{{$attr ?? null}}

	{{! empty($submit) && $submit ? 'type="submit"' : null}}

	{{! empty($disabled) && $disabled ? 'disabled' : null}}
	
	{{! empty($external) && $external ? 'target="_blank"' : null}}

	@isset($data)
		@foreach($data as $data => $value)
		data-{{$data}}="{{$value}}"
		@endforeach
	@endisset
	
	class="btn 
			btn-{{array_find($styles ?? null, ['size'])}} 
			btn-{{array_find($styles ?? null, ['theme'])}} 
			bg-{{array_find($styles ?? null, ['background'])}} 
			mt-{{array_find($styles ?? null, ['mt'])}}
			mb-{{array_find($styles ?? null, ['mb'])}}
			ml-{{array_find($styles ?? null, ['ml'])}}
			mr-{{array_find($styles ?? null, ['mr'])}} 
			text-{{array_find($styles ?? null, ['text'])}} 
			{{array_find($styles ?? null, ['shadow']) == true ? 'shadow' : null}} 
			{{$classes ?? null}}">

	{!! $label !!}

</{{empty($href) ? 'button' : 'a'}}>

{{-- @else

<button
	type="{{$type ?? null}}"
	{{$attr ?? null}}

	@if(!empty($modal))
	data-toggle="modal" data-target="#{{$modal}}"
	@endif

	@if(!empty($collapse))
	data-toggle="collapse" data-target="#{{$collapse}}"
	@endif

	@if(!empty($tab))
	data-toggle="tab" data-target="#{{$tab}}"
	@endif

	@if(!empty($disabled) && $disabled)
	disabled
	@endif

	@empty($raw)
	class="btn btn-{{$size ?? null}} btn-{{!empty($wide) ? 'wide' : null}} btn-{{$theme ?? null}} bg-{{$color ?? null}} text-{{$text ?? null}} {{$classes ?? null}}"
	@else
	style="border: none; background: transparent; padding: 0"
	@endempty>{!! $label !!}
</button>
@endif --}}