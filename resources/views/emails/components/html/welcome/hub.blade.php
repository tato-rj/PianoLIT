<tr><td class="welcome-hub-row">
<table class="welcome-hub" dir="{{ ($imageLeft ?? false) ? 'rtl' : 'ltr' }}" role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
<td class="welcome-hub-copy" dir="ltr" width="66%" valign="middle" align="left">
<h3>{{ $title }}</h3>
@isset($status)<p class="welcome-hub-status">{{ $status }}</p>@endisset
{{ Illuminate\Mail\Markdown::parse($slot) }}
</td>
<td class="welcome-hub-gap" width="6%" aria-hidden="true">&nbsp;</td>
<td class="welcome-hub-image" dir="ltr" width="28%" valign="middle">
<img src="{{ $image }}" width="140" height="140" alt="{{ $alt }}">
</td>
</tr></table>
</td></tr>
