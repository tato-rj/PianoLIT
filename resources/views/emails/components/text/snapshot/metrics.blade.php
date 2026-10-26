## {{ $title }}
@foreach($metrics as $metric)
{{ $metric['label'] }}: {{ number_format($metric['value']) }}
@if(array_key_exists('change', $metric))
{{ $metric['change'] > 0 ? '+' : ($metric['change'] < 0 ? '−' : '') }}{{ number_format(abs($metric['change'])) }} vs last week
@else
{{ $metric['note'] }}
@endif

@endforeach
{{ $comparison }}
Signups: {{ number_format($origins['webapp']) }} web app · {{ number_format($origins['ios']) }} iOS · {{ number_format($origins['web']) }} website
@if($origins['other'])
 · {{ number_format($origins['other']) }} other / unknown
@endif

