@component('emails.admin.template')
# Hi {{$recipient->first_name}}!

Here is the breakdown of our past <u>week</u>.

@foreach($reports as $report)
@component('mail::panel')
# {{$report['title']}}
We had <strong>{{$report['data']->count()}} new {{strtolower(str_plural($report['name'], $report['data']->count()))}}</strong>.
@foreach($report['data'] as $data)
@if($data->report_name)
<div style="margin-bottom: .3em; {{$loop->first ? 'margin-top: .75em; padding-top: .75em; border-top: 1px solid #dce3e9' : null}}"><small>{{$data->report_name}}</small></div>
@endif
@endforeach
@endcomponent
@endforeach

@endcomponent
