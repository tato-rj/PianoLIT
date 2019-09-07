<tr>
  <td>{{$piece->short_name}}</td>
  <td style="white-space: nowrap;">{{$piece->composer->short_name}}</td>
  <td>
    <div class="cursor-pointer badge badge-pill bg-{{strtolower($piece->level->name)}}">{{ucfirst($piece->level->name)}}</div>
  </td>
  <td class="d-flex justify-content-end align-items-center">
    @include('admin.components.play', ['audio' => storage($piece->audio_path)])
    <div class="ml-2 {{$piece->lookup('score_path')}}">
      <a href="{{storage($piece->score_path)}}" target="_blank"><i class="fas fa-file-alt"></i></a>
    </div>
    <div class="ml-2">
      <a href="{{route('admin.pieces.edit', $piece->id)}}" target="_blank" class="text-warning"><i class="far fa-eye"></i></a>
    </div>
    <div>
      <a href="" class="text-primary add-piece ml-2" data-id="{{$piece->id}}"><i class="fas fa-plus-circle"></i>
        <div style="display: none;">
          @include('admin.pages.playlists.piece')
        </div>
      </a>
    </div>
  </td>
</tr>
