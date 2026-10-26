<tr><td class="snapshot-section welcome-padding">
<h2>{{ $title }}</h2>
<table class="snapshot-grid" role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
@foreach($platforms as $platform)
<td class="snapshot-platform-cell" width="50%" valign="top"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td class="snapshot-platform {{ $loop->first ? 'snapshot-platform-web' : 'snapshot-platform-ios' }}">
<h3>{{ $platform['title'] }}</h3>
<p class="snapshot-platform-number">{{ number_format($platform['value']) }}</p>
<p class="snapshot-label">{{ $platform['unit'] }}</p>
<table class="snapshot-actions" role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
@foreach($platform['rows'] as [$label, $total])
<tr><td>{{ $label }}</td><td class="snapshot-action-count" align="right">{{ number_format($total) }}</td></tr>
@endforeach
</table>
<p class="snapshot-note">{{ $platform['note'] }}</p>
</td></tr></table></td>
@endforeach
</tr></table>
</td></tr>
