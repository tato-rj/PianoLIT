<tr><td class="snapshot-section welcome-padding">
<h2>{{ $title }}</h2>
<table class="snapshot-grid" role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
@foreach(array_chunk($metrics, 2) as $row)
<tr>
@foreach($row as $metric)
<td class="snapshot-metric-cell" width="50%" valign="top"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td class="snapshot-metric">
<p class="snapshot-label">{{ $metric['label'] }}</p>
<p class="snapshot-number">{{ number_format($metric['value']) }}</p>
@if(array_key_exists('change', $metric))
<p class="snapshot-change {{ $metric['change'] > 0 ? 'snapshot-up' : ($metric['change'] < 0 ? 'snapshot-down' : '') }}">{{ $metric['change'] > 0 ? '+' : ($metric['change'] < 0 ? '−' : '') }}{{ number_format(abs($metric['change'])) }} <span>vs last week</span></p>
@else
<p class="snapshot-note">{{ $metric['note'] }}</p>
@endif
</td></tr></table></td>
@endforeach
</tr>
@endforeach
</table>
<p class="snapshot-note snapshot-comparison">{{ $comparison }}</p>
<p class="snapshot-origins">Signups: <strong>{{ number_format($origins['webapp']) }}</strong> web app · <strong>{{ number_format($origins['ios']) }}</strong> iOS · <strong>{{ number_format($origins['web']) }}</strong> website
@if($origins['other'])
 · <strong>{{ number_format($origins['other']) }}</strong> other / unknown
@endif
</p>
</td></tr>
