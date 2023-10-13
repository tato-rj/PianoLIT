@component('mail::panel')
# {{$report['title']}}
We had <strong>{{$report['data']->count()}} new {{strtolower(str_plural($report['name'], $report['data']->count()))}}</strong>.
@php($difference = $report['data']->count() - $report['pastData']->count())
@if($difference > 0)
<div style="color: #3D4852; font-size: 16px"><strong style="color: #28a745">{{hex('plus') . abs($difference)}}</strong> from the previous period.</div>
@elseif($difference < 0)
<div style="color: #3D4852; font-size: 16px"><strong style="color: #dc3545">{{hex('minus') . abs($difference)}}</strong> from the previous period.</div>
@endif

<ul style="margin: 0; padding: 0;">

@foreach($report['data'] as $data)
@include('emails.admin.report.records')
@endforeach

</ul>
@endcomponent