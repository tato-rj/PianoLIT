@component('mail::raw')

{!! $lesson->dynamic('body', $subscription) !!}

<p style="font-size: 94%">
    If you wish to stop receiving these emails, please send your request to <a href="mailto:contact@pianolit.com">contact@pianolit.com</a>.
</p>
@endcomponent
