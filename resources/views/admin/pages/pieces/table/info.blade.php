  <div class="d-flex">
    <div class="dropdown d-inline-block align-text-bottom cursor-pointer mr-1">
      <i class="fas fa-ellipsis-v dropdown-toggle" data-toggle="dropdown"></i>
      <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
        <a href="{{$item->timeline_url}}" target="_blank" class="dropdown-item">Timeline</a>
        <a href="{{route('api.pieces.collection', $item->id)}}" target="_blank" class="dropdown-item">Collection</a>
        <a href="{{route('api.pieces.similar', $item->id)}}" target="_blank" class="dropdown-item">More like this</a>
        <a href="{{route('pieces.show', $item->id)}}" target="_blank" class="dropdown-item">Score</a>
      </div>
    </div>
    <div class="d-flex hide-on-sm">
      <span class="{{$item->curiosity ? 'text-primary' : 'text-muted'}}" title="{{$item->curiosity}}"><i class="fas fa-info-circle mr-1"></i></span>
      @include('admin.components.play', ['audio' => storage($item->audio_path)])
      <span class="mx-1 {{$item->videos_count > 0 ? 'text-primary' : 'text-muted'}}"><i class="fab fa-youtube mr-1"></i>{{$item->videos_count}}</span>
      <span class="{{$item->itunes_count > 0 ? 'text-primary' : 'text-muted'}}"><i class="fab fa-itunes mr-1"></i>{{$item->itunes_count}}</span>
    </div>
  </div>