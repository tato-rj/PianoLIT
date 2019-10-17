@component('mail::panel')
@foreach($composersDied as $composer)
<div>
	<img src="{{asset('images/icons/reaper.png')}}" style="margin-right: .5rem; width: 20px">
	{{$composer->name . ' died ' . now()->diffInYears($composer->date_of_death) . ' years ago on ' . $composer->date_of_death->toFormattedDateString() . '.'}}
</div>
{{-- @php($timeline = \App\Timeline::fromYear($composer->date_of_death->year))
@if($timeline->exists())
@include('emails.timeline.sameyear')
@endif --}}
@endforeach
@endcomponent