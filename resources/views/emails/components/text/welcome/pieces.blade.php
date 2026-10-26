## {{ $title }}

{{ $slot }}

@foreach($pieces as $piece)
{{ $piece['title'] }} — {{ $piece['composer'] }}: {{ $piece['url'] }}

@endforeach
{{ $button }}: {{ $url }}
