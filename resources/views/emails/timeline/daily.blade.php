@component('mail::message')
# On the {{now()->format('jS \o\f F')}}...

@if($composersBorn->count() > 0)
@include('emails.timeline.born')
@endif

@if($composersDied->count() > 0)
@include('emails.timeline.died')
@endif

@component('mail::button', ['url' => ''])
Recommend this to a friend
@endcomponent

@endcomponent
