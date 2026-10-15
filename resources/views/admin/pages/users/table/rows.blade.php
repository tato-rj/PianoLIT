@foreach($rows as $row)
<tr>
    <td class="text-nowrap">{!! $row['created_at'] !!}</td>
    <td>{{ $row['id'] }}</td>
    <td class="dataTables_main_column">{!! $row['name'] !!}</td>
    <td class="text-nowrap">{!! $row['origin_display'] !!}</td>
    <td class="text-nowrap">{!! $row['status'] !!}</td>
    <td class="text-nowrap">{!! $row['super_user_display'] !!}</td>
    <td class="text-nowrap">{!! $row['actions'] !!}</td>
</tr>
@endforeach
