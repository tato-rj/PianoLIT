<i title="{{$title ?? null}}" 
	class="fa{{$fa_type ?? 's'}} 
		   fa-{{$icon}} 
		   text-{{$color ?? null}} 
		   fa-{{$size ?? null}} 
		   mr-{{$mr ?? 2}} 
		   ml-{{$ml ?? null}} 
		   {{$classes ?? null}}"
	style="{{isset($if) && ! $if ? 'display: none;' : null}}"></i>