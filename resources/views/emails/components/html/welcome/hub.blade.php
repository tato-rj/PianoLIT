<tr><td class="welcome-hub-row">
<table class="welcome-hub" role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
<td class="welcome-hub-image" width="30%" valign="middle" align="left">
<img src="{{ $image }}" width="128" height="128" alt="{{ $alt }}">
</td>
<td class="welcome-hub-copy" width="70%" valign="middle" align="left">
<h3>{{ $title }}</h3>
@isset($status)<p class="welcome-hub-status">{{ $status }}</p>@endisset
{{ Illuminate\Mail\Markdown::parse($slot) }}
</td>
</tr></table>
</td></tr>
