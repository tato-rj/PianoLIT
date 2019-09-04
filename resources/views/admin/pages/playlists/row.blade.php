<tr>
  <td>{{$piece->short_name}}</td>
  <td style="white-space: nowrap;">{{$piece->composer->short_name}}</td>
  <td>
    <div class="cursor-pointer badge badge-pill bg-{{strtolower($piece->level->name)}}">{{ucfirst($piece->level->name)}}</div>
  </td>
  <td class="d-flex justify-content-end align-items-center">
    @include('admin.components.play', ['audio' => storage($piece->audio_path)])
    <div class="mx-2">
      <a href="{{route('admin.pieces.edit', $piece->id)}}" target="_blank" class="text-muted"><i class="far fa-eye"></i></a>
    </div>
    <div>
      <a href="" class="text-muted add-piece" data-id="{{$piece->id}}"><i class="fas fa-plus-circle"></i></a>
    </div>
    <div style="display: none;">
      @include('admin.pages.playlists.piece')
    </div>
    </a>
  </td>
</tr>
