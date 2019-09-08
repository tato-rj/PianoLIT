<tr>
  <td>{{$piece->short_name}}</td>
  <td style="white-space: nowrap;">{{$piece->composer->short_name}}</td>
  <td>
    <div class="cursor-pointer badge badge-pill bg-{{strtolower($piece->level->name)}}">{{ucfirst($piece->level->name)}}</div>
  </td>
  <td class="d-flex justify-content-end align-items-center">
    @include('admin.components.play', ['audio' => storage($piece->audio_path)])
    <div class="ml-2">
      @if($piece->is_public_domain)
      <a href="{{storage($piece->score_path)}}" target="_blank" class="{{$piece->lookup('score_path')}}"><i class="fas fa-file-alt"></i></a>
      @else
      <a href="{{$piece->score_url}}" target="_blank" class="test-success"><i class="fas fa-globe"></i></a>
      @endif
    </div>
    <div class="ml-2">
      <a href="{{route('admin.pieces.edit', $piece->id)}}" target="_blank" class="text-warning"><i class="far fa-eye"></i></a>
    </div>
    <div>
      <div class="text-primary cursor-pointer add-piece ml-2" data-id="{{$piece->id}}"><i class="fas fa-plus-circle"></i>
        <div style="display: none;">
          @include('admin.pages.playlists.piece')
        </div>
      </div>
    </div>
  </td>
</tr>
