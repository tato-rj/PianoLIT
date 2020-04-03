@foreach($requests as $request)
  <tr>
    <td class="text-nowrap">{{$request->created_at->toFormattedDateString()}}</td>
    <td class="dataTables_main_column">{{$request->piece->medium_name}}</td>
    <td class="text-nowrap">{{$request->piece->composer->name}}</td>
    <td>{{ucfirst($request->piece->level_name)}}</td>
  </tr>
@endforeach