@component('mail::snapshot.panel', ['title' => $title, 'description' => $description])
@forelse($countries['rows'] as $country)
{{ $country['flag'] }} {{ $country['name'] }}: {{ number_format($country['total']) }} ({{ number_format($country['share'], 1) }}%)
@empty
No countries recorded for this week’s active accounts
@endforelse
@if($countries['total'])
{{ number_format($countries['located']) }} of {{ number_format($countries['total']) }} have a saved country · {{ number_format($countries['unlocated']) }} unknown
@endif
@endcomponent
