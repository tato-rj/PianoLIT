<tr><td class="welcome-footer welcome-padding" align="left">

<table class="editorial-footer-details" role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
<td class="editorial-footer-copy" valign="top">
<p class="editorial-footer-note">{{ $footerReason }}</p>
@unless($internal ?? false)
<p class="editorial-footer-note">Questions? Just reply, or say hello at <a href="mailto:contact@pianolit.com">contact@pianolit.com</a>.</p>
<p class="editorial-footer-links"><a href="{{ $unsubscribeUrl }}">{{ $unsubscribeLabel ?? 'Unsubscribe / email preferences' }}</a><span> | </span><a href="{{ $websiteUrl.route('privacy', [], false) }}">Privacy</a><span> | </span><a href="mailto:contact@pianolit.com">Help</a><span> | </span><a href="{{ $websiteUrl.route('password.request', [], false) }}">Forgot password</a></p>
@endunless
<p class="welcome-copyright">© {{ date('Y') }} PianoLIT</p>
</td>
</tr></table>
</td></tr>
