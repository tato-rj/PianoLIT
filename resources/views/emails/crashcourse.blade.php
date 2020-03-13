@component('mail::raw', compact('lesson'))
{!! $lesson->dynamic('body', $subscription) !!}
@endcomponent
