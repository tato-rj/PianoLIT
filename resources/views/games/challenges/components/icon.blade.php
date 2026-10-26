<i aria-hidden="true" title="{{$title ?? null}}"
	name="{{$name ?? null}}"
	class="fa{{$fa_type ?? 's'}}
		   fa-{{$icon}}
		   text-{{$fa_color ?? null}}
		   fa-{{$fa_size ?? null}}
		   mr-{{$mr ?? 2}}
		   ml-{{$ml ?? null}}
		   t-2
		   {{$classes ?? null}}"
	style="{{isset($if) && ! $if ? 'display: none;' : null}} {{$styles ?? null}}"></i>
