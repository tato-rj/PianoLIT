@if($data->report_name)
<li style="margin-bottom: .2em; margin-left: 1.2em; {{$loop->first ? 'margin-top: .75em' : null}}"><small>{{$data->report_name}}</small></li>
@endif