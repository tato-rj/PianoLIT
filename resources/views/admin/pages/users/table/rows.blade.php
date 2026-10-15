@foreach($users as $item)
<tr>
    <td class="text-nowrap">@if($item->created_at)<time datetime="{{ $item->created_at->toIso8601String() }}">{{ $item->created_at->toFormattedDateString() }}</time>@endif</td>
    <td>{{ $item->id }}</td>
    <td class="admin-table__account">@include('admin.pages.users.table.name')</td>
    <td class="text-nowrap">@include('admin.pages.users.table.origin')</td>
    <td class="text-nowrap">
        @php($elements = $item->statusElements())
        <span class="admin-table__badge admin-table__badge--{{ $elements['color'] }}">{{ $elements['label'] }}</span>
    </td>
    <td class="text-nowrap">@include('admin.pages.users.table.actions')</td>
</tr>
@endforeach
