<tr><td class="freepick-hub welcome-padding">
<table class="freepick-hub-card" role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#e5edff"><tr><td class="freepick-hub-content">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
<td class="freepick-hub-art" width="104" valign="middle"><img src="{{ $image }}" width="104" height="104" alt=""></td>
<td class="freepick-hub-copy" valign="middle" align="left">
<h2>{{ $title }}</h2>
{{ Illuminate\Mail\Markdown::parse($slot) }}
<a class="freepick-hub-action" href="{{ $url }}" target="_blank" rel="noopener noreferrer">{{ $button }} <span aria-hidden="true">&rarr;</span></a>
</td>
</tr></table>
</td></tr></table>
</td></tr>
