@component('mail::editorial.message', ['title' => 'Welcome to PianoLIT!', 'preheader' => 'Your next favorite piano piece is waiting. Welcome to PianoLIT.', 'websiteUrl' => $websiteUrl, 'footerReason' => 'You’re receiving this email because you signed up for a PianoLIT account.', 'unsubscribeUrl' => $unsubscribeUrl])
{{ $slot }}
@endcomponent
