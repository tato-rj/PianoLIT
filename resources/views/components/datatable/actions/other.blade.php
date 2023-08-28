@foreach($action as $data)
@if($data['route'])
<a href="{{$data['route']}}" disabled title="{{$data['title']}}" target="{{! array_key_exists('target', $data) ? '_blank' : null}}" class="text-muted mr-2"><i class="fas fa-{{$data['icon']}} align-middle"></i></a>
@endif
@endforeach