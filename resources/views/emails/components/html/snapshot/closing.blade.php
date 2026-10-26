<tr><td class="snapshot-closing welcome-padding" align="center">
@component('mail::welcome.button', ['url' => $url])
{{ $button }}
@endcomponent
<div class="snapshot-method">{{ Illuminate\Mail\Markdown::parse($slot) }}</div>
</td></tr>
