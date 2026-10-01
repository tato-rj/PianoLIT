@if($item->score)
<a href="{{route('admin.pieces.test-escore', $item)}}" class="link-blue" target="_blank">@icon('file')</a>
@endif
{{$item->long_name}}
@if(! $item->hasAudio())
<a href="{{youtube($item->long_name . ' by ' . $item->composer->name)}}" target="_blank" class="link-blue">@icon('external-link', ['mr' => 0, 'classes' => 'ms-1 icon-size-xs'])</a>
@endif