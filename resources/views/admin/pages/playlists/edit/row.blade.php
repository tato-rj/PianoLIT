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
      <a href="{{storage($item->score_path)}}" target="_blank" class="{{$item->lookup('score_path')}}">@icon('file-text', ['mr' => 0])</a>
      @else
      <a href="{{$item->score_url}}" target="_blank" class="test-success">@icon('globe', ['mr' => 0])</a>
      @endif
    </div>
    <div class="ml-2">
      <a href="{{route('admin.pieces.edit', $item->id)}}" target="_blank" class="text-warning">@icon('eye', ['mr' => 0])</a>
    </div>
    <div>
      <div class="text-primary cursor-pointer add-piece ml-2" data-id="{{$item->id}}">@icon('circle-plus', ['mr' => 0])
        <div style="display: none;">
          @include('admin.pages.playlists.edit.piece', ['piece' => $item])
        </div>
      </div>
    </div>
  </td>
</tr>
