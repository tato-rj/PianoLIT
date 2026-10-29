<!--[if mso]><table role="presentation" width="{{ \App\Support\EmailLayout::WIDTH }}" align="center" cellpadding="0" cellspacing="0" border="0"><tr><td><![endif]-->
<table class="email-container{{ empty($class) ? '' : ' '.$class }}" role="presentation" width="{{ \App\Support\EmailLayout::WIDTH }}" align="center" cellpadding="0" cellspacing="0" border="0">
{{ $slot }}
</table>
<!--[if mso]></td></tr></table><![endif]-->
