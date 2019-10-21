<div id="playlists-overview" class="modal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Overview</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
          @foreach($playlists as $playlist)
          <div class="col-lg-3 col-md-4 col-12 mb-2">
            <div class="bg-light text-muted mb-2 rounded px-1"><strong><span class="text-blue mr-2">{{$loop->iteration}}</span>{{$playlist->name}}</strong></div>
            <div class="px-2">
              @forelse($playlist->pieces as $piece)
              <div class="mb-1">
                <div style="line-height: 1" class="d-flex">
                  @include('admin.components.play', ['audio' => storage($piece->audio_path)])
                  <div class="ml-1 text-truncate">
                    <small>{{$loop->iteration}}. {{$piece->medium_name}}</small>
                    <div class="text-muted" style="line-height: 1"><small>by {{$piece->composer->short_name}}</small></div>
                  </div>
                </div>
              </div>
              @empty
              <p class="text-muted ml-2"><small>This playlist is empty</small></p>
              @endforelse
            </div>
          </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</div>