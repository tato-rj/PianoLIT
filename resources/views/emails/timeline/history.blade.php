<hr style="opacity: 0.3; margin: 3rem 0;">
<strong style="text-align: center; width: 100%;"><span style="margin-right: .25em">🧐</span>Also in that time...</strong>
@component('mail::panel')
@foreach($history as $event)
<div style="display: flex; align-items: flex-start; margin-bottom: {{$loop->last ? null : '.75rem'}}">
	<div>
		<div style="background-color: #48a59d; color: white; border-radius: 4px; padding: .15rem .4rem; white-space: nowrap; font-weight: bold; font-size: .85em">{{$event->year}}</div>
	</div>
	<div style="flex-grow: 1; margin: 0 .5rem">
		<p>{{$event->event}}</p>
	</div>
	<div>
		<a href="{{$event->url}}" target="_blank" style="color: #3490dc; text-decoration: none; white-space: nowrap;">Read more</a>
	</div>
</div>
@endforeach
@endcomponent