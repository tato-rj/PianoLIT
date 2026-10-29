<tr><td class="welcome-hero welcome-padding" align="center">
@component('mail::editorial.logo', ['websiteUrl' => $websiteUrl])
@endcomponent
<h1>{{ $title ?? 'Welcome, '.$firstName }}</h1>
<div class="welcome-introduction">{{ Illuminate\Mail\Markdown::parse($slot) }}</div>
</td></tr>
