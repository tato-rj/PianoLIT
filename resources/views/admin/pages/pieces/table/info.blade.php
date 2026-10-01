  <div class="d-flex">
    <div class="dropdown d-inline-block align-text-bottom cursor-pointer me-1">
      @icon('ellipsis-vertical', ['mr' => 0, 'classes' => 'dropdown-toggle', 'attributes' => ['data-bs-toggle' => 'dropdown']])
      <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
        @if($item->hasAudio())
        <a href="{{route('clips.piece', $item)}}" target="_blank" class="dropdown-item">Clip</a>
        @endif
        <a href="{{$item->timeline_url}}" target="_blank" class="dropdown-item">Timeline</a>
        <a href="{{route('api.pieces.collection', $item->id)}}" target="_blank" class="dropdown-item">Collection</a>
        <a href="{{route('api.pieces.similar', $item->id)}}" target="_blank" class="dropdown-item">More like this</a>
      </div>
    </div>
    <div class="d-flex hide-on-sm">
      <span class="{{$item->hasDescription() ? 'text-primary' : 'text-muted'}} me-1" title="{{$item->description}}">@icon('info', ['mr' => 0])</span>
      @include('admin.components.play', ['audio' => storage($item->audio_path)])
      <span class="text-nowrap mx-1 {{$item->tutorials_count > 0 ? 'text-primary' : 'text-muted'}}">@icon('brand-youtube', ['mr' => 0])</span>
      <span class="{{$item->hasTutorials(['synthesia']) ? 'text-danger' : 'text-muted'}}">@icon('flame', ['mr' => 0])</span>
    </div>
  </div>
