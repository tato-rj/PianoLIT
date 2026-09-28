{{$item->long_name}}
@if(! $item->hasAudio())
<a href="{{youtube($item->long_name . ' by ' . $item->composer->name)}}" target="_blank" class="link-blue">@icon('external-link', ['mr' => 0, 'classes' => 'ml-1 icon-size-xs'])</a>
@endif