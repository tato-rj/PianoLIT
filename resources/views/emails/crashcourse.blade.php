@component('mail::raw')
{!! $lesson->cancelUrl($subscription) !!}
{{-- {!! $lesson->dynamic('body', $subscription) !!} --}}


@endcomponent
