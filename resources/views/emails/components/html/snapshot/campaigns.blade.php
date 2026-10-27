@component('mail::snapshot.panel', ['title' => $title, 'description' => $description, 'url' => $url, 'reportLabel' => $reportLabel])
<p class="snapshot-campaign-totals">@if(empty($campaigns['traffic_in_google']))<strong>{{ number_format($campaigns['totals']['arrivals']) }}</strong> arrivals · @endif<strong>{{ number_format($campaigns['totals']['trials']) }}</strong> trials · <strong>{{ number_format($campaigns['totals']['first_paid']) }}</strong> first payments</p>
@if(count($campaigns['rows']))
<table class="snapshot-campaign-list" width="100%" cellpadding="0" cellspacing="0" border="0" aria-label="Top campaigns">
<thead><tr><th width="{{ !empty($campaigns['traffic_in_google']) ? 64 : 46 }}%" align="left" scope="col">Campaign</th>@if(empty($campaigns['traffic_in_google']))<th width="18%" align="right" scope="col">Arrivals</th>@endif<th width="18%" align="right" scope="col">Trials</th><th width="18%" align="right" scope="col">First paid</th></tr></thead>
<tbody>
@foreach($campaigns['rows'] as $campaign)
<tr><td class="snapshot-campaign-name"><strong>{!! str_replace(['_', '-', '.'], ['_<wbr>', '-<wbr>', '.<wbr>'], e($campaign['campaign'] ?: ($campaign['source'] ?: $campaign['medium']))) !!}</strong><p class="snapshot-note">{{ $campaign['campaign'] ? implode(' · ', array_filter([$campaign['source'], $campaign['medium']])) : ($campaign['source'] ? 'Source only'.($campaign['medium'] ? ' · '.$campaign['medium'] : '') : 'Medium only') }}</p></td>@if(empty($campaigns['traffic_in_google']))<td align="right">{{ number_format($campaign['arrivals']) }}</td>@endif<td align="right">{{ number_format($campaign['trials']) }}</td><td class="snapshot-campaign-paid" align="right">{{ number_format($campaign['first_paid']) }}</td></tr>
@endforeach
</tbody></table>
@else
<p>No campaign activity recorded this week</p>
@endif
<p class="snapshot-note snapshot-source-note">{{ !empty($campaigns['traffic_in_google']) ? 'First Stripe payments · opted-in campaign links only · traffic in Google Analytics' : 'First Stripe payments · opted-in outcomes may follow earlier arrivals' }}</p>
@endcomponent
