<tr><td class="snapshot-section welcome-padding">
<table class="snapshot-panel" role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td class="snapshot-panel-content">
<h2>{{ $title }}</h2>
@if(!empty($description))<p class="snapshot-note snapshot-panel-description">{{ $description }}</p>@endif
{{ $slot }}
@if(!empty($url))<p class="snapshot-report"><a href="{{ $url }}">{{ $reportLabel }} →</a></p>@endif
</td></tr></table>
</td></tr>
