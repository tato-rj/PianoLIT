<hr style="opacity: 0.3; margin: 2rem 0;">
<strong style="text-align: center; width: 100%;">Also happening around the world...</strong>
@component('mail::panel')
@foreach($history->get() as $event)
<div style="display: flex; align-items: flex-start; margin-bottom: {{$loop->last ? null : '.75rem'}}">
	<div style="background-color: #48a59d; color: white; border-radius: 4px; padding: .15rem .4rem; margin-right: .5rem; white-space: nowrap; font-weight: bold;">{{$event->year}}</div>
	<p>{{$event->event}}</p>
</div>
@endforeach
@endcomponent