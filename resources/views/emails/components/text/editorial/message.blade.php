{{ $slot }}

@component('mail::editorial.footer', ['websiteUrl' => $websiteUrl, 'footerReason' => $footerReason, 'unsubscribeUrl' => $unsubscribeUrl, 'unsubscribeLabel' => $unsubscribeLabel ?? 'Unsubscribe / email preferences'])
@endcomponent
