<tr><td class="welcome-hub-row">
<table class="welcome-hub welcome-hub-{{ $color ?? 'sky' }}" dir="{{ ($imageLeft ?? false) ? 'rtl' : 'ltr' }}" role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
<td class="welcome-hub-copy" dir="ltr" width="66%" valign="middle" align="left">
<h3>{{ $title }}</h3>
@isset($status)<p class="welcome-hub-status">{{ $status }}</p>@endisset
{{ Illuminate\Mail\Markdown::parse($slot) }}
</td>
<td class="welcome-hub-image" dir="ltr" width="34%" valign="middle" align="center">
<img src="{{ $image }}" width="160" height="160" alt="{{ $alt }}">
</td>
</tr></table>
</td></tr>
