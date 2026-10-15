<a class="admin-table__primary" href="{{ route('admin.users.show', $item) }}">{{ $item->full_name }}</a>{!! $item->location ? $item->location->countryFlag : null !!}
<small>{{ $item->email }}</small>
