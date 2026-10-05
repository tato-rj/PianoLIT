<div class="playlist-player border-top border-bottom bg-white {{ !empty($piecePlayer) ? 'playlist-player--piece' : '' }}" data-playlist-player hidden aria-label="{{ !empty($piecePlayer) ? 'Piece audio player' : 'Playlist audio player' }}">
    <div class="playlist-player__inner">
        <div class="playlist-player__identity"><img class="rounded-sm" data-player-artwork src="{{asset('images/webapp/thumbnail.jpg')}}" alt=""><div><span data-player-title>Select a piece</span><span class="text-muted" data-player-composer></span>
            </div>
            @if(!empty($piecePlayer))
            <button class="btn-raw text-muted playlist-player__close" type="button" data-player-close aria-label="Close audio player">@icon('close', ['mr' => 0])</button>
            @endif
        </div>
        <div class="playlist-player__timeline text-muted"><span data-elapsed>0:00</span><div class="playlist-player__progress"><input class="form-range" type="range" min="0" max="0" step="0.1" value="0" data-seek aria-label="Playback position" disabled><div class="playlist-player__markers" data-section-markers aria-label="Section markers"></div></div><span data-duration>0:00</span></div>
        <div class="playlist-player__transport">
            <button class="btn-raw" type="button" data-previous aria-label="{{ !empty($piecePlayer) ? 'Restart recording' : 'Previous piece' }}">@icon('skip-back', ['mr' => 0, 'filled' => true])</button>
            <button class="btn btn-secondary btn-sm playlist-player__toggle" type="button" data-player-toggle aria-label="Play" disabled><span data-play-icon>@icon('play', ['mr' => 0, 'filled' => true])</span><span data-pause-icon hidden>@icon('pause', ['mr' => 0, 'filled' => true])</span></button>
            <button class="btn-raw" type="button" data-next aria-label="Next piece" @if(!empty($piecePlayer)) disabled @endif>@icon('skip-forward', ['mr' => 0, 'filled' => true])</button>
        </div>
        <div class="playlist-player__volume"><button class="btn-raw text-muted" type="button" data-mute aria-label="Mute" aria-pressed="false">@icon('volume-2', ['mr' => 0])</button><input class="form-range" type="range" min="0" max="1" step="0.01" value="0.5" data-volume aria-label="Volume"></div>
        <div class="playlist-player__speed dropdown dropup">
            <button class="btn btn-secondary btn-sm dropdown-toggle d-inline-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Playback speed"><span data-speed-label>1.0×</span></button>
            <div class="dropdown-menu dropdown-menu-end" aria-label="Playback speed">
                @foreach([0.5, 0.75, 1, 1.25, 1.5, 2] as $rate)
                <button class="dropdown-item d-flex align-items-center justify-content-between gap-3 {{$rate == 1 ? 'active' : ''}}" type="button" data-speed="{{$rate}}" aria-pressed="{{$rate == 1 ? 'true' : 'false'}}"><span>{{$rate == 1 || $rate == 2 ? number_format($rate, 1) : $rate}}×</span><span data-speed-check @if($rate != 1) hidden @endif>@icon('check', ['mr' => 0])</span></button>
                @endforeach
            </div>
        </div>
        <button class="btn btn-secondary btn-sm playlist-player__sections-toggle d-flex align-items-center justify-content-center gap-2" type="button" data-sections-toggle aria-label="Sections" aria-expanded="false" aria-controls="playlist-sections" hidden>@icon('list', ['mr' => 0])<span data-sections-chevron>@icon('chevron-down', ['mr' => 0])</span></button>
    </div>
    @if(!empty($piecePlayer))
    <p class="playlist-player__status small text-muted" role="status" aria-live="polite" data-playlist-status></p>
    @endif
    @include('webapp.playlist.sections')
</div>
