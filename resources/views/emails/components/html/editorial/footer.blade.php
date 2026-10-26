<tr><td class="welcome-footer welcome-padding" align="left">
@unless($internal ?? false)
<table class="editorial-footer-social" role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
<td class="editorial-footer-youtube"><a href="{{ config('services.channels.youtube') }}" target="_blank" rel="noopener noreferrer" aria-label="Visit our YouTube channel"><img src="{{ $websiteUrl.mix('images/emails/youtube.png') }}" width="32" height="23" alt="YouTube"></a></td>
</tr></table>
@endunless
<table class="editorial-footer-details" role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
<td class="editorial-footer-copy" valign="top">
<p class="editorial-footer-note">{{ $footerReason }}</p>
@unless($internal ?? false)
<p class="editorial-footer-note">Questions? Just reply, or say hello at <a href="mailto:contact@pianolit.com">contact@pianolit.com</a>.</p>
<p class="editorial-footer-links"><a href="{{ $unsubscribeUrl }}">{{ $unsubscribeLabel ?? 'Unsubscribe / email preferences' }}</a><span> | </span><a href="{{ $websiteUrl.route('privacy', [], false) }}">Privacy</a><span> | </span><a href="mailto:contact@pianolit.com">Help</a><span> | </span><a href="{{ $websiteUrl.route('password.request', [], false) }}">Forgot password</a></p>
@endunless
<p class="welcome-copyright">© {{ date('Y') }} PianoLIT</p>
</td>
<td class="editorial-footer-brand" width="72" align="right" valign="bottom"><a href="{{ $websiteUrl }}">
@component('mail::editorial.logo', ['websiteUrl' => $websiteUrl, 'shadow' => false])
@endcomponent
</a></td>
</tr></table>
</td></tr>
