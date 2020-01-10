{{$item->long_name}}
@if(! $item->hasAudio())
<a href="{{youtube($item->long_name . ' by ' . $item->composer->name)}}" target="_blank" class="link-blue"><i class="fas fa-external-link-alt ml-1 fa-xs"></i></a>
@endif