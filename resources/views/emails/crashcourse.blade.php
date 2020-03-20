@component('mail::raw', ['action' => $action])

{!! $lesson->dynamic('body', $subscription) !!}

<p style="font-size: 94%">
    If you wish to stop receiving these emails <a href="{{$action}}" target="_blank">click here</a> you can update your preferences or email your request to contact@pianolit.com.
</p>
@endcomponent
