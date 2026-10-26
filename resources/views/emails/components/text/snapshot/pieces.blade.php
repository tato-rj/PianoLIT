## {{ $title }}
@if(count($pieces))
Most explored by distinct accounts
@foreach($pieces as $piece)
{{ $loop->iteration }}. {{ $piece['title'] }} — {{ number_format($piece['total']) }} accounts ({{ number_format($piece['web']) }} web · {{ number_format($piece['ios']) }} iOS)
{{ $piece['url'] ?? route('webapp.pieces.show', $piece['id']) }}
@endforeach
@else
No piece exploration recorded this week
@endif
