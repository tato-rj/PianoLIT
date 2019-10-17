@component('mail::panel')
@foreach($composersBorn as $composer)
<div>
	<img src="{{asset('images/icons/birthday-cake.png')}}" style="margin-right: .5rem; width: 20px">
	{{$composer->name . ' was born ' . now()->diffInYears($composer->date_of_birth) . ' years ago on ' . $composer->date_of_birth->toFormattedDateString() . '.'}}
</div>
@endforeach

@php($timeline = \App\Timeline::fromYear($composer->date_of_birth->year))
@if($timeline->exists())
@include('emails.timeline.sameyear')
@endif
@endcomponent