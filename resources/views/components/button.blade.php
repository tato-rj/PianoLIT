<{{empty($href) ? 'button' : 'a'}}
	@isset($href)
	href="{{$href}}" 
	@endisset

	@isset($id)
	id="{{$id}}" 
	@endisset

	{{! empty($nofollow) && $nofollow ? 'rel="nofollow"' : null}}

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
			{{array_find($styles ?? null, ['pill']) == true ? 'rounded-pill' : null}} 
			{{$classes ?? null}}">

	{!! $label !!}

</{{empty($href) ? 'button' : 'a'}}>
