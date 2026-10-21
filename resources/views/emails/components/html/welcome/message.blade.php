<!DOCTYPE html>
<html lang="en">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="color-scheme" content="light">
<title>Welcome to PianoLIT!</title>
<link href="https://fonts.googleapis.com/css?family=Poppins:400,600&amp;display=swap" rel="stylesheet">
<style>
@media only screen and (max-width: 620px) {
    .welcome-container { width: 100% !important; }
    .welcome-padding { padding-left: 24px !important; padding-right: 24px !important; }
    .welcome-hero h1 { font-size: 30px !important; }
    .welcome-actions td { display: block !important; padding: 5px 0 !important; }
}
</style>
</head>
<body>
<div class="welcome-preheader">Your next favorite piano piece is waiting. Welcome to PianoLIT.</div>
<table class="welcome-wrapper" role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
<tr><td align="center">
<!--[if mso]><table role="presentation" width="600"><tr><td><![endif]-->
<table class="welcome-container" role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" align="center">
{{ $slot }}
<tr><td class="welcome-footer welcome-padding" align="center">
<p>Questions? Just reply, or say hello at<br><a href="mailto:contact@pianolit.com">contact@pianolit.com</a>.</p>
<p>Enjoy the music,<br><strong>The PianoLIT team</strong></p>
<p class="welcome-copyright">© {{ date('Y') }} PianoLIT</p>
</td></tr>
</table>
<!--[if mso]></td></tr></table><![endif]-->
</td></tr>
</table>
</body>
</html>
