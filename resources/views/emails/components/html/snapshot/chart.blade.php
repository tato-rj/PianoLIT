<tr><td class="snapshot-section welcome-padding">
<h2>{{ $title }}</h2>
@php($peak = max(1, max(array_column($days, 'total'))))
<table class="snapshot-chart" role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
@foreach($days as $day)
<td width="14.28%" valign="bottom" align="center">
<p class="snapshot-day-number">{{ number_format($day['total']) }}</p>
<table role="presentation" width="65%" cellpadding="0" cellspacing="0" border="0" align="center">
<tr><td height="{{ 68 - (int) round(64 * $day['total'] / $peak) }}" style="height: {{ 68 - (int) round(64 * $day['total'] / $peak) }}px; font-size: 0; line-height: 0;">&nbsp;</td></tr>
<tr><td class="snapshot-bar" bgcolor="{{ $day['total'] ? '#0055fe' : '#dee2e6' }}" height="{{ max(2, (int) round(64 * $day['total'] / $peak)) }}" style="height: {{ max(2, (int) round(64 * $day['total'] / $peak)) }}px; font-size: 0; line-height: 0;">&nbsp;</td></tr>
</table>
<p class="snapshot-day-label">{{ $day['label'] }}</p>
</td>
@endforeach
</tr></table>
</td></tr>
