<tr><td class="welcome-section welcome-padding" align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td class="welcome-rule"></td></tr></table>
<h2>{{ $title }}</h2>
<div class="welcome-description">{{ Illuminate\Mail\Markdown::parse($slot) }}</div>
<img class="welcome-mockup" src="{{ $image }}" width="504" alt="{{ $alt }}">
<table class="welcome-actions" role="presentation" cellpadding="0" cellspacing="0" border="0" align="center"><tr>
@foreach($links as $link)
<td align="center" valign="middle">@component('mail::welcome.button', $link){{ $link['label'] }}@endcomponent</td>
@endforeach
</tr></table>
</td></tr>
