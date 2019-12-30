<tr>
  <td class="dataTables_main_column">{{$item->short_name}}</td>

  <td class="text-nowrap">{{$item->composer->short_name}}</td>
  
  <td>
    <div class="cursor-pointer badge badge-pill bg-{{strtolower($item->level->name)}}">{{ucfirst($item->level->name)}}</div>
  </td>
  
  <td class="d-flex justify-content-end align-items-center">
    @include('admin.components.play', ['audio' => storage($item->audio_path)])
    <div class="ml-2">
      @if($item->is_public_domain)
      <a href="{{storage($item->score_path)}}" target="_blank" class="{{$item->lookup('score_path')}}"><i class="fas fa-file-alt"></i></a>
      @else
      <a href="{{$item->score_url}}" target="_blank" class="test-success"><i class="fas fa-globe"></i></a>
      @endif
    </div>
    <div class="ml-2">
      <a href="{{route('admin.pieces.edit', $item->id)}}" target="_blank" class="text-warning"><i class="far fa-eye"></i></a>
    </div>
    <div>
      <div class="text-primary cursor-pointer add-piece ml-2" data-id="{{$item->id}}"><i class="fas fa-plus-circle"></i>
        <div style="display: none;">
          @include('admin.pages.playlists.edit.piece', ['piece' => $item])
        </div>
      </div>
    </div>
  </td>
</tr>
