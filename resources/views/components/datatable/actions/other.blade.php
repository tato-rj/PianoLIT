@foreach($action as $data)
<a href="{{$data['route']}}" title="{{$data['title']}}" target="{{! empty($stay) && $stay ? null : '_blank'}}" class="text-muted mr-2"><i class="fas fa-{{$data['icon']}} align-middle"></i></a>
@endforeach