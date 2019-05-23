<tr data-toggle="tooltip" data-placement="bottom" data-html="true" title="Short name: <b>{{$piece->short_name}}</b>">
  <td style="font-size: .8rem; white-space: nowrap; vertical-align: middle;">
    <a href="{{$piece->timeline_url}}" title="JSON response to timeline" target="_blank" class="text-brand mr-1"><i class="fas fa-list-ul"></i></a>
    @include('admin.pages.pieces.play-icon')
    <span class="mx-1 {{$piece->youtube_count > 0 ? 'text-brand' : 'text-muted'}}"><i class="fab fa-youtube mr-1"></i>{{$piece->youtube_count}}</span>
    <span class="{{$piece->itunes_count > 0 ? 'text-brand' : 'text-muted'}}"><i class="fab fa-itunes mr-1"></i>{{$piece->itunes_count}}</span>
    </div>
  </td>
  <td>{{$piece->long_name}}</td>
  <td>{{$piece->tags()->count()}}</td>
  <td style="white-space: nowrap;">{{$piece->composer->short_name}}</td>
  <td><div class="badge badge-pill bg-{{strtolower($piece->level->name)}}">{{ucfirst($piece->level->name)}}</div></td>

  <td class="text-right" style="white-space: nowrap;">
    @created($piece)
    <a href="{{route('admin.pieces.edit', $piece->id)}}" class="text-muted mr-2"><i class="far fa-edit align-middle"></i></a>
    <a href="" data-name="{{$piece->name}}" data-url="{{route('admin.pieces.destroy', $piece->id)}}" data-toggle="modal" data-target="#delete-modal" class="delete text-muted"><i class="far fa-trash-alt align-middle"></i></a>
    @else
    <a href="{{route('admin.pieces.edit', $piece->id)}}" target="_blank" class="text-muted mr-2"><i class="far fa-eye align-middle"></i></a>
    @endcreated
  </td>

</tr>
