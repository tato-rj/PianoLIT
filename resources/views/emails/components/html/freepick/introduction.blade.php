<tr><td class="freepick-introduction welcome-padding" align="center">
@if(trim((string) $slot))<p class="freepick-description">{{ $slot }}</p>@endif
@component('mail::welcome.button', ['url' => $url]){{ $button }}@endcomponent
</td></tr>
