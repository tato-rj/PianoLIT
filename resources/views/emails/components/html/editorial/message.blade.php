<!DOCTYPE html>
<html lang="en">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="color-scheme" content="light">
<title>{{ $title }}</title>
<link href="https://fonts.googleapis.com/css?family=Poppins:400,600&amp;display=swap" rel="stylesheet">
<style>
@media only screen and (max-width: 620px) {
    .welcome-container { width: 100% !important; }
    .welcome-padding { padding-left: 24px !important; padding-right: 24px !important; }
    .welcome-hero h1 { font-size: 30px !important; }
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
}
</style>
</head>
<body bgcolor="#f8f9fa">
<div class="welcome-preheader">{{ $preheader }}</div>
<table class="welcome-wrapper" role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f8f9fa">
<tr><td align="center">
<!--[if mso]><table role="presentation" width="600"><tr><td><![endif]-->
<table class="welcome-container{{ isset($emailClass) ? ' '.$emailClass : '' }}" role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" align="center">
{{ $slot }}
@component('mail::editorial.footer', ['websiteUrl' => $websiteUrl, 'footerReason' => $footerReason, 'unsubscribeUrl' => $unsubscribeUrl, 'unsubscribeLabel' => $unsubscribeLabel ?? 'Unsubscribe / email preferences'])
@endcomponent
</table>
<!--[if mso]></td></tr></table><![endif]-->
</td></tr>
</table>
</body>
</html>
