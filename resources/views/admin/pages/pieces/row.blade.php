<div class="col-12 mb-2">
  <div class="d-flex justify-content-between px-3 py-2 
  {{is_null($piece->creator_id) ? 'bg-white text-muted border' : 'bg-'.strtolower($piece->level->name)}} rounded">
    <div class="truncate">
      @include('admin.pages.pieces.play')
      <strong>{{$piece->long_name}}</strong> by {{$piece->composer->short_name}}
    </div>
    <div class="text-right ml-2 d-flex" style="white-space: nowrap;">
      <div class="mr-2">
        <span class="mr-1"><small><i class="fab fa-youtube mr-1"></i>{{$piece->youtube_count}}</small></span>
        <span class="mr-1"><small><i class="fab fa-itunes mr-1"></i>{{$piece->itunes_count}}</small></span>
      </div>
      <div class="text-brand">
        @created($piece)
        <a href="{{route('admin.pieces.edit', $piece->id)}}">edit</a> | <a href="" data-name="{{$piece->name}}" data-url="{{route('admin.pieces.edit', $piece->id)}}" data-toggle="modal" data-target="#delete-modal" class="delete">delete</a>
        @else
        <a href="{{route('admin.pieces.edit', $piece->id)}}">view details</a>
        @endcreated
      </div>
    </div>
  </div>
</div>