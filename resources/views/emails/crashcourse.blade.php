@component('mail::raw', compact('subscription'))
{!! $lesson->dynamic('body', $subscription) !!}
@endcomponent
