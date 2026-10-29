<tr><td class="welcome-section welcome-padding" align="center">
@if(!empty($title))
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td class="welcome-rule"></td></tr></table>
<h2>{{ $title }}</h2>
@endif
@if(trim((string) $slot))
<div class="welcome-description">{{ Illuminate\Mail\Markdown::parse($slot) }}</div>
@endif
@isset($note)
<div class="welcome-section-note">{{ Illuminate\Mail\Markdown::parse($note) }}</div>
@endisset
@if(!empty($image))
<img class="welcome-mockup" src="{{ $image }}" width="504" alt="{{ $alt }}">
@endif
<table class="welcome-actions" role="presentation" cellpadding="0" cellspacing="0" border="0" align="center"><tr>
@foreach($links as $link)
<td align="center" valign="middle">@component('mail::welcome.button', $link){{ $link['label'] }}@endcomponent</td>
@endforeach
</tr></table>
</td></tr>
