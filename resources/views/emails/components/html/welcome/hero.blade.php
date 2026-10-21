<tr><td class="welcome-hero welcome-padding" align="center">
<img class="welcome-logo" src="{{ $image }}" width="130" height="122" alt="PianoLIT">
<h1>Welcome, {{ $firstName }}.</h1>
<div class="welcome-introduction">{{ Illuminate\Mail\Markdown::parse($slot) }}</div>
</td></tr>
