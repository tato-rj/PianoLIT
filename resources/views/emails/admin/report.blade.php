@component('emails.admin.template')
# Hi {{$recipient->first_name}}!

Here is the breakdown of our past <u>week</u>.

@foreach($reports as $report)
@component('mail::panel')
# {{$report['title']}}
We had <strong>{{$report['data']->count()}} new {{strtolower(str_plural($report['name'], $report['data']->count()))}}</strong>.
<ul style="margin: 0; padding: 0;">
@foreach($report['data'] as $data)
@if($data->report_name)
<li style="margin-bottom: .2em; margin-left: 1.2em; {{$loop->first ? 'margin-top: .75em' : null}}"><small>{{$data->report_name}}</small></li>
@endif
@endforeach
</ul>
@endcomponent
@endforeach

@endcomponent
