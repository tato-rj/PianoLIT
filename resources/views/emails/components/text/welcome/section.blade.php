## {{ $title }}

{{ $slot }}

@foreach($links as $link)
{{ $link['label'] }}: {{ $link['url'] }}
@endforeach
