<tr><td class="welcome-section welcome-hubs welcome-padding" align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td class="welcome-rule"></td></tr></table>
<h2>{{ $title }}</h2>
<div class="welcome-description"><p>{{ $introduction }}</p></div>
<table class="welcome-hub-list" role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
{{ $slot }}
</table>
@component('mail::welcome.button', ['url' => $url]){{ $button }}@endcomponent
</td></tr>
