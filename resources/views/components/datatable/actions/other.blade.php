@foreach($action as $data)
@if($data['route'])
<a href="{{$data['route']}}" disabled title="{{$data['title']}}" target="{{! array_key_exists('target', $data) ? '_blank' : null}}" class="text-muted mr-2">@icon($data['icon'], ['mr' => 0, 'classes' => 'align-middle'])</a>
@endif
@endforeach