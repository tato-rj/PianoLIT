@component('mail::editorial.message', ['title' => 'Welcome to PianoLIT!', 'preheader' => 'Your PianoLIT account is ready.', 'websiteUrl' => $websiteUrl, 'footerReason' => 'You’re receiving this email because you signed up for a PianoLIT account.', 'unsubscribeUrl' => $unsubscribeUrl])
{{ $slot }}
@endcomponent
