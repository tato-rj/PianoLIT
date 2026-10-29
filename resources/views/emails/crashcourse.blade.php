@component('mail::raw', ['footerReason' => 'You’re receiving this lesson because you signed up for a PianoLIT crash course.', 'unsubscribeUrl' => 'mailto:contact@pianolit.com', 'unsubscribeLabel' => 'Cancel this course'])

{!! $lesson->dynamic('body', $subscription) !!}

<p style="font-size: 94%">
    If you wish to stop receiving these emails, please send your request to <a href="mailto:contact@pianolit.com">contact@pianolit.com</a>.
</p>
@endcomponent
