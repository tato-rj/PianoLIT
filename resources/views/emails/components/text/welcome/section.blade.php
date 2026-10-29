@if(!empty($title))
## {{ $title }}
@endif

{{ $slot }}

{{ $note ?? '' }}

@foreach($links as $link)
{{ $link['label'] }}: {{ $link['url'] }}
@endforeach
