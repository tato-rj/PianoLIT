  <div class="d-flex">
    <div class="dropdown d-inline-block align-text-bottom cursor-pointer mr-1">
      <i class="fas fa-ellipsis-v dropdown-toggle" data-toggle="dropdown"></i>
      <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
        <a href="{{route('clips.piece', $item)}}" target="_blank" class="dropdown-item">Clip</a>
        <a href="{{$item->timeline_url}}" target="_blank" class="dropdown-item">Timeline</a>
        <a href="{{route('api.pieces.collection', $item->id)}}" target="_blank" class="dropdown-item">Collection</a>
        <a href="{{route('api.pieces.similar', $item->id)}}" target="_blank" class="dropdown-item">More like this</a>
      </div>
    </div>
    <div class="d-flex hide-on-sm">
      <span class="{{$item->hasDescription() ? 'text-primary' : 'text-muted'}} mr-1" title="{{$item->description}}"><i class="fas fa-info-circle"></i></span>
      @include('admin.components.play', ['audio' => storage($item->audio_path)])
      <span class="text-nowrap mx-1 {{$item->tutorials_count > 0 ? 'text-primary' : 'text-muted'}}"><i class="fab fa-youtube"></i></span>
      <span class="{{$item->hasTutorials(['synthesia']) ? 'text-danger' : 'text-muted'}}"><i class="fas fa-fire"></i></span>
    </div>
  </div>