<tr><td class="welcome-section welcome-benefits welcome-padding" align="center">
<h2>{{ $title }}</h2>
<table class="welcome-benefit-list" role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
{{ $slot }}
</table>
@if(!empty($url))
@component('mail::welcome.button', ['url' => $url]){{ $button }}@endcomponent
@endif
</td></tr>
