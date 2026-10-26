{{ $slot }}

@component('mail::editorial.footer', ['websiteUrl' => $websiteUrl, 'footerReason' => $footerReason, 'unsubscribeUrl' => $unsubscribeUrl ?? null, 'unsubscribeLabel' => $unsubscribeLabel ?? 'Unsubscribe / email preferences', 'internal' => $internal ?? false])
@endcomponent
