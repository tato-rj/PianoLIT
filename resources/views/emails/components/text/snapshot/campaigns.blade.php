@component('mail::snapshot.panel', ['title' => $title, 'description' => $description, 'url' => $url, 'reportLabel' => $reportLabel])
{{ empty($campaigns['traffic_in_google']) ? number_format($campaigns['totals']['arrivals']).' arrivals · ' : '' }}{{ number_format($campaigns['totals']['trials']) }} trials · {{ number_format($campaigns['totals']['first_paid']) }} first payments

@foreach($campaigns['rows'] as $campaign)
{{ $campaign['campaign'] ?: ($campaign['source'] ?: $campaign['medium']) }} — {{ $campaign['campaign'] ? implode(' · ', array_filter([$campaign['source'], $campaign['medium']])) : ($campaign['source'] ? 'Source only'.($campaign['medium'] ? ' · '.$campaign['medium'] : '') : 'Medium only') }}
{{ empty($campaigns['traffic_in_google']) ? 'Arrivals: '.number_format($campaign['arrivals']).' · ' : '' }}Trials: {{ number_format($campaign['trials']) }} · First paid: {{ number_format($campaign['first_paid']) }}
@endforeach
@if(!count($campaigns['rows']))
No campaign activity recorded this week
@endif

{{ !empty($campaigns['traffic_in_google']) ? 'First Stripe payments · opted-in campaign links only · traffic in Google Analytics' : 'First Stripe payments · opted-in outcomes may follow earlier arrivals' }}
@endcomponent
