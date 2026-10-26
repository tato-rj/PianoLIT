## {{ $title }}
@foreach($platforms as $platform)
{{ $platform['title'] }}: {{ number_format($platform['value']) }} {{ $platform['unit'] }}
@foreach($platform['rows'] as [$label, $total])
{{ $label }}: {{ number_format($total) }}
@endforeach
{{ $platform['note'] }}

@endforeach
