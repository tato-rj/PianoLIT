@component('mail::raw', compact(['subscription', 'cancel_url']))
{!! $lesson->dynamic('body', $subscription) !!}
@endcomponent
