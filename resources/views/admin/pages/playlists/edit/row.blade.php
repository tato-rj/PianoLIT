<tr>
  <td class="dataTables_main_column">{{$item->short_name}}</td>

  <td class="text-nowrap">{{$item->composer->short_name}}</td>

  <td>
    <div class="cursor-pointer badge rounded-pill bg-{{strtolower($item->level->name)}}">{{ucfirst($item->level->name)}}</div>
  </td>

  <td>@include('admin.pages.playlists.edit.table.actions')</td>
</tr>
