@component('mail::raw')

{!! $lesson->dynamic('body', $subscription) !!}

<p>
    If you wish to stop receiving these emails <a href="{{$action}}" target="_blank">click here</a> you can update your preferences or email your request to contact@pianolit.com.
</p>
@endcomponent
