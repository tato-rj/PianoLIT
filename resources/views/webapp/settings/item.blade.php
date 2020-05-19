<a 
@isset($url)
href="{{$url}}"
@endisset

@isset($external)
target="_blank" 
@endisset

class="list-group-item py-4 rounded-0 list-group-item-action">{!! $label !!}</a>