<tr>
  <td style="font-size: .8rem; white-space: nowrap; vertical-align: middle;">
    <a href="{{$piece->timeline_url}}" title="JSON response to timeline" target="_blank" class="text-primary mr-1"><i class="fas fa-list-ul"></i></a>
    <span class="{{$piece->curiosity ? 'text-primary' : 'text-muted'}}" title="{{$piece->curiosity}}"><i class="fas fa-info-circle mr-1"></i></span>
    @include('admin.pages.pieces.play-icon')
    <span class="mx-1 {{$piece->youtube_count > 0 ? 'text-primary' : 'text-muted'}}"><i class="fab fa-youtube mr-1"></i>{{$piece->youtube_count}}</span>
    <span class="{{$piece->itunes_count > 0 ? 'text-primary' : 'text-muted'}}"><i class="fab fa-itunes mr-1"></i>{{$piece->itunes_count}}</span>
    </div>
  </td>
  <td>{{$piece->long_name}}</td>
  <td style="white-space: nowrap;">{{$piece->composer->short_name}}</td>
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
