## {{ $piece->medium_name }}

{{ $piece->attribution }}by {{ $piece->composer->name }}
@if($piece->level_name)
{{ ucfirst($piece->extended_level_name) }}
@endif

{{ $url }}
