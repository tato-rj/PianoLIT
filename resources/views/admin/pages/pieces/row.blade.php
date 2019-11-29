<tr>
  <td style="font-size: .8rem; white-space: nowrap; vertical-align: middle;" class="d-flex">
    <div class="dropdown d-inline-block align-text-bottom cursor-pointer mr-1">
      <i class="fas fa-ellipsis-v dropdown-toggle align-middle" data-toggle="dropdown"></i>
      <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
        <a href="{{$piece->timeline_url}}" target="_blank" class="dropdown-item">Timeline</a>
        <a href="{{route('api.pieces.collection', $piece->id)}}" target="_blank" class="dropdown-item">Collection</a>
        <a href="{{route('api.pieces.similar', $piece->id)}}" target="_blank" class="dropdown-item">More like this</a>
        <a href="{{route('pieces.show', $piece->id)}}" target="_blank" class="dropdown-item">Score</a>
      </div>
    </div>
    <div class="d-flex">
      <span class="{{$piece->curiosity ? 'text-primary' : 'text-muted'}}" title="{{$piece->curiosity}}"><i class="fas fa-info-circle mr-1"></i></span>
      @include('admin.components.play', ['audio' => storage($piece->audio_path)])
      <span class="mx-1 {{$piece->videos_count > 0 ? 'text-primary' : 'text-muted'}}"><i class="fab fa-youtube mr-1"></i>{{$piece->videos_count}}</span>
      <span class="{{$piece->itunes_count > 0 ? 'text-primary' : 'text-muted'}}"><i class="fab fa-itunes mr-1"></i>{{$piece->itunes_count}}</span>
    </div>
    </div>
  </td>
  <td>{{$piece->long_name}}
    @if(! $piece->hasAudio())
    <a href="{{youtube($piece->long_name . ' by ' . $piece->composer->name)}}" target="_blank" class="link-blue"><i class="fas fa-external-link-alt ml-1 fa-xs"></i></a>
    @endif
  </td>
  <td style="white-space: nowrap;">{{$piece->composer->short_name}}</td>
  <td style="white-space: nowrap;">
    @if($rcm = $piece->getRanking('rcm'))
    <div class="badge badge-pill alert-blue"><strong>RCM {{$rcm}}</strong></div>
    @endif
    @if($abrsm = $piece->getRanking('abrsm'))
    <div class="badge badge-pill alert-blue"><strong>ABRSM {{$abrsm}}</strong></div>
    @endif
  </td>
  <td class="position-relative">
    <span class="badge badge-light badge-popup cursor-pointer" id="badge-tag-{{$piece->id}}">{{$piece->tags_count}}</span>
    <div class="position-absolute bg-white shadow-sm border p-2 rounded popup mb-3" data-url="{{route('admin.pieces.load-tags', $piece->id)}}" style="top: 10px; display: none; z-index: 2; right: 0; width: 720px">
      @include('admin.pages.pieces.popups.content')
    </div>
  </td>
  <td class="position-relative">
    <div class="badge-popup cursor-pointer badge badge-pill bg-{{strtolower($piece->level->name)}}" 
        data-original-class="bg-{{strtolower($piece->level->name)}}" 
        data-original-id="{{$piece->level->id}}" 
        id="badge-level-{{$piece->id}}">{{ucfirst($piece->level->name)}}</div>
    <div class="position-absolute bg-white shadow-sm border px-2 pt-2 pb-1 rounded popup mb-3"  data-url="{{route('admin.pieces.load-levels', $piece->id)}}" style="top: 10px; display: none; z-index: 1; left: 0">
    @include('admin.pages.pieces.popups.content')
    </div>
  </td>

  <td class="text-right" style="white-space: nowrap;">
    @created($piece)
    <a href="{{route('admin.pieces.edit', $piece->id)}}" class="text-muted mr-2"><i class="far fa-edit align-middle"></i></a>
    <a href="" data-name="{{$piece->name}}" data-url="{{route('admin.pieces.destroy', $piece->id)}}" data-toggle="modal" data-target="#delete-modal" class="delete text-muted"><i class="far fa-trash-alt align-middle"></i></a>
    @else
    <a href="{{route('admin.pieces.edit', $piece->id)}}" target="_blank" class="text-muted mr-2"><i class="far fa-eye align-middle"></i></a>
    @endcreated
  </td>
</tr>
