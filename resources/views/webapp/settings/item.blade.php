<a 
@isset($url)
href="{{$url}}"
@endisset

@if($external ?? false)
target="_blank" rel="noopener noreferrer"
@endif

class="list-group-item py-4 rounded-0 list-group-item-action">{!! $label !!}</a>
