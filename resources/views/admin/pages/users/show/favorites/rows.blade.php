@foreach($pieces as $piece)
  <tr>
    <td class="dataTables_main_column">{{$piece->medium_name}}</td>
    <td class="text-nowrap">{{$piece->composer->name}}</td>
    <td>{{ucfirst($piece->level_name)}}</td>
  </tr>
@endforeach