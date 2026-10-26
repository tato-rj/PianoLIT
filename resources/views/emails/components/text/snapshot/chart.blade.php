## {{ $title }}
@foreach($days as $day)
{{ $day['label'] }}: {{ number_format($day['total']) }}
@endforeach
