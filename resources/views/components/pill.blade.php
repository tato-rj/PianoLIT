@if($if ?? true)
<span class="badge badge-pill bg-{{$color ?? null}} alert-{{$theme ?? null}} absolute-{{$pos ?? null}} text-{{$text ?? null}} noselect {{$classes ?? null}}" style="{{$styles ?? null}}">{!! $label !!}</span>
@endif