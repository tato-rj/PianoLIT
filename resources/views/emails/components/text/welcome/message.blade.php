@component('mail::editorial.message', ['websiteUrl' => $websiteUrl, 'footerReason' => 'You’re receiving this email because you signed up for a PianoLIT account.', 'unsubscribeUrl' => $unsubscribeUrl])
{{ $slot }}
@endcomponent
