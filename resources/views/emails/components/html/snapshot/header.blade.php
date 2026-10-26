<tr><td class="snapshot-header welcome-padding" align="center">
@component('mail::editorial.logo', ['websiteUrl' => $websiteUrl, 'shadow' => false])
@endcomponent
<p class="snapshot-eyebrow">WEEKLY SNAPSHOT</p>
<h1>{{ $title }}</h1>
<p>{{ $period['label'] }}</p>
<p class="snapshot-note">Sunday–Saturday</p>
</td></tr>
