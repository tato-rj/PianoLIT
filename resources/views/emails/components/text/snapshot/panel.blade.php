## {{ $title }}
@if(!empty($description))
{{ $description }}
@endif

{{ $slot }}
@if(!empty($url))
{{ $reportLabel }}: {{ $url }}
@endif
