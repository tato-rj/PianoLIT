<a class="admin-table__primary" href="{{ route('admin.users.show', $item) }}">{{ $item->full_name }}</a>{!! $item->location ? $item->location->countryFlag : null !!}
<small>{{ $item->email }}</small>
@if($item->location)
@php($locationLabel = collect([$item->location->cityName, $item->location->regionName, $item->location->countryName])->filter()->unique()->implode(', '))
@if($locationLabel)<small>{{ $locationLabel }}</small>@endif
@endif
