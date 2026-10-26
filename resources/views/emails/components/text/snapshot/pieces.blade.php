@component('mail::snapshot.panel', ['title' => $title, 'description' => 'Distinct web app + iOS accounts'])
@if(count($growing))
Gaining momentum
@foreach($growing as $piece)
{{ $piece['title'] }}: +{{ number_format($piece['change']) }} ({{ number_format($piece['previous']) }} → {{ number_format($piece['total']) }} accounts vs last week)
{{ $piece['url'] ?? route('webapp.pieces.show', $piece['id']) }}
@endforeach
@endif
@if(count($pieces))
Most explored
@foreach($pieces as $piece)
{{ $loop->iteration }}. {{ $piece['title'] }} — {{ number_format($piece['total']) }} accounts ({{ number_format($piece['web']) }} web · {{ number_format($piece['ios']) }} iOS)
{{ $piece['url'] ?? route('webapp.pieces.show', $piece['id']) }}
@endforeach
@else
No piece exploration recorded this week
@endif
@endcomponent
