<button
	{{$attr ?? null}}
	@if(!empty($modal))
	data-toggle="modal" data-target="#{{$modal}}"
	@endif
	@empty($raw)
	class="btn btn-{{$size ?? null}} btn-{{!empty($wide) ? 'wide' : null}} btn-{{$theme ?? null}} bg-{{$color ?? null}} text-{{$text ?? null}} {{$classes ?? null}}"
	@else
	style="border: none; background: transparent; padding: 0"
	@endempty>{!! $label !!}</button>