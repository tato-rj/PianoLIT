@component('mail::message')
# Hello there!

On this date, <strong>{{now()->format('jS \o\f F')}}</strong> here's what we found...

<div style="margin-bottom: 1.5rem"></div>

@if($composersBorn->count() > 0)
@include('emails.timeline.born')
@endif

@if($composersDied->count() > 0)
@include('emails.timeline.died')
@endif

<div style="margin-bottom: 4rem"></div>

@endcomponent
