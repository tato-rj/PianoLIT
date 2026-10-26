<tr><td class="freepick-premium welcome-padding" align="center">
<h2>{{ $title }}</h2>
<div class="freepick-premium-copy">{{ Illuminate\Mail\Markdown::parse($slot) }}</div>
<img class="welcome-mockup" src="{{ $image }}" width="504" alt="PianoLIT library on the web and in the iOS app">
@component('mail::welcome.button', ['url' => $url]){{ $button }}@endcomponent
</td></tr>
<tr><td class="freepick-footer-gap" height="32"></td></tr>
