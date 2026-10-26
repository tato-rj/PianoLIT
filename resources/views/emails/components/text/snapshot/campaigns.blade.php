@component('mail::snapshot.panel', ['title' => $title, 'description' => $description, 'url' => $url, 'reportLabel' => $reportLabel])
{{ number_format($campaigns['totals']['arrivals']) }} arrivals · {{ number_format($campaigns['totals']['trials']) }} trials · {{ number_format($campaigns['totals']['first_paid']) }} first payments

@foreach($campaigns['rows'] as $campaign)
{{ $campaign['campaign'] ?: ($campaign['source'] ?: $campaign['medium']) }} — {{ $campaign['campaign'] ? implode(' · ', array_filter([$campaign['source'], $campaign['medium']])) : ($campaign['source'] ? 'Source only'.($campaign['medium'] ? ' · '.$campaign['medium'] : '') : 'Medium only') }}
Arrivals: {{ number_format($campaign['arrivals']) }} · Trials: {{ number_format($campaign['trials']) }} · First paid: {{ number_format($campaign['first_paid']) }}
@endforeach
@if(!count($campaigns['rows']))
No campaign activity recorded this week
@endif

First Stripe payments · opted-in outcomes may follow earlier arrivals
@endcomponent
