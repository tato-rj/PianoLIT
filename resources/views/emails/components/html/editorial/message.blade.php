<!DOCTYPE html>
<html lang="en">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="color-scheme" content="light">
@include('mail::layout.styles')
<title>{{ $title }}</title>
<link href="https://fonts.googleapis.com/css?family=Poppins:400,600&amp;display=swap" rel="stylesheet">
<style>
@media only screen and (max-width: {{ \App\Support\EmailLayout::MOBILE_BREAKPOINT }}px) {
    .welcome-hero h1 { font-size: 30px !important; }
    .welcome-benefit-preview { width: 120px !important; }
    .welcome-benefit-mockup { width: 120px !important; height: 160px !important; }
    .welcome-benefit-with-mockup .welcome-benefit-copy { padding-left: 16px !important; }
    .welcome-actions td { display: block !important; padding: 5px 0 !important; }
    .welcome-hub-copy { padding-left: 16px !important; }
    .welcome-hub-image img { width: 100% !important; height: auto !important; }
    .welcome-piece { padding-left: 4px !important; padding-right: 4px !important; }
    .freepick-portrait { width: 60px !important; padding-right: 16px !important; }
    .freepick-portrait img { width: 60px !important; height: 60px !important; }
    .freepick-piece h2 { font-size: 23px !important; }
    .freepick-cover { height: 170px !important; }
    .freepick-cover-space, .freepick-cover-link { height: 146px !important; }
    .freepick-cover-link { line-height: 146px !important; }
    .freepick-latest .welcome-piece { display: block !important; width: 100% !important; padding: 0 0 18px !important; overflow: hidden; }
    .freepick-latest .welcome-piece img { float: left !important; width: 88px !important; margin: 0 14px 0 0 !important; }
    .snapshot-header h1 { font-size: 27px !important; }
    .snapshot-metric { padding: 16px 12px !important; }
    .snapshot-number { font-size: 34px !important; }
    .snapshot-platform-cell { display: block !important; width: 100% !important; box-sizing: border-box; padding: 0 0 12px !important; }
    .snapshot-piece-art { width: 64px !important; }
    .snapshot-piece-art img { width: 64px !important; }
    .snapshot-member-content { padding: 20px 16px !important; }
    .snapshot-panel-content { padding: 20px 16px !important; }
    .snapshot-campaign-list th { font-size: 11px !important; }
}
</style>
</head>
<body bgcolor="#f8f9fa">
<div class="welcome-preheader">{{ $preheader }}</div>
<table class="welcome-wrapper" role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f8f9fa">
<tr><td align="center">
@component('mail::layout.container', ['class' => 'welcome-container'.(isset($emailClass) ? ' '.$emailClass : '')])
{{ $slot }}
@component('mail::editorial.footer', ['websiteUrl' => $websiteUrl, 'footerReason' => $footerReason, 'unsubscribeUrl' => $unsubscribeUrl ?? null, 'unsubscribeLabel' => $unsubscribeLabel ?? 'Unsubscribe / email preferences', 'internal' => $internal ?? false])
@endcomponent
@endcomponent
</td></tr>
</table>
</body>
</html>
