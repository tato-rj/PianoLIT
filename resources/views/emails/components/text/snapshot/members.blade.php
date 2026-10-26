## {{ $title }}
{{ number_format($members['engaged']) }} of {{ number_format($members['total']) }} used PianoLIT
This week’s activity among members with access now
@foreach($labels as $key => $label)
{{ $label }}: {{ number_format($members[$key]) }}
@endforeach
@if($members['review'])
{{ number_format($members['review']) }} need review: {{ $url }}?status=needs_review
@endif
Stripe + Apple as of {{ $asOf }}
Active includes billing grace; trials and ending access are separate
{{ $url }}
