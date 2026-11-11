@component('mail::editorial.message', ['title' => $lesson->dynamic('subject', $subscription), 'preheader' => $design['settings']['preheader'], 'websiteUrl' => \App\Support\EmailLayout::websiteUrl(), 'footerReason' => 'You’re receiving this lesson because you signed up for a PianoLIT crash course.', 'unsubscribeUrl' => 'mailto:contact@pianolit.com', 'unsubscribeLabel' => 'Cancel this course'])
@component('mail::courses.lesson', ['design' => $design, 'builderPreview' => $builderPreview ?? false])
@endcomponent
@endcomponent
