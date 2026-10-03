@php($canReorder = isset($folder) && $folder->user_id == auth('web')->id())
<div class="playlist-actions">
    <button class="btn btn-primary" type="button" data-play-all disabled>@icon('play', ['mr' => 0, 'filled' => true])<span>Play all</span></button>
    <button class="btn btn-secondary" type="button" data-shuffle aria-pressed="false" disabled>@icon('shuffle', ['mr' => 0])Shuffle</button>
    <button class="btn btn-secondary" type="button" data-loop aria-pressed="false" disabled>@icon('repeat', ['mr' => 0])<span>Loop: Off</span></button>
</div>
<p class="playlist-status small text-muted" role="status" aria-live="polite" data-playlist-status></p>
<div class="playlist-tracks border-top {{$canReorder ? '' : 'playlist-tracks--readonly'}}" data-playlist-tracks @if($canReorder) data-url-reorder="{{route('webapp.users.favorites.folders.reorder', $folder)}}" @endif>
    @forelse($pieces as $piece)
    @php($hasTrackAccess = $piece->hasWebMediaAccess(auth('web')->user()))
    @php($audioMoments = collect(optional($piece->tutorials->first())->listeningMoments() ?: [])->map(function ($moment) { return \Illuminate\Support\Arr::except($moment, ['comment']); })->all())
    <article class="playlist-track border-bottom" data-track data-id="{{isset($favorites) ? $favorites->values()->get($loop->index)->id : $piece->id}}"
        data-has-score="{{$piece->score_path ? 'true' : 'false'}}" data-piece-id="{{$piece->id}}" data-title="{{$piece->short_name}}" data-composer="{{$piece->composer->short_name}}"
        data-audio="{{$piece->audio_path ? $piece->audio : ''}}" data-preview="{{$hasTrackAccess ? 0 : config('webapp.media_preview_seconds')}}"
        data-audio-moments="{{json_encode($audioMoments)}}"
        data-artwork="{{$piece->cover_path ? storage($piece->cover_path) : asset(optional($piece->period)->cover_image ?: 'images/webapp/thumbnail.jpg')}}">
        <span class="playlist-track__number text-muted" data-track-number>{{$loop->iteration}}</span>
        <button class="btn btn-secondary btn-sm playlist-track__play" type="button" data-track-play aria-label="Play {{$piece->short_name}}" @unless($piece->audio_path) disabled title="Audio unavailable" @endunless>
            <span data-play-icon>@icon('play', ['mr' => 0, 'filled' => true])</span><span data-pause-icon hidden>@icon('pause', ['mr' => 0, 'filled' => true])</span>
        </button>
        <span class="playlist-track__level bg-{{$piece->level_name}}-raw" title="{{ucfirst($piece->level_name ?: 'Unknown')}} level"></span>
        <div class="playlist-track__identity"><span class="playlist-track__title" title="{{$piece->short_name}}">{{$piece->short_name}}</span><span class="playlist-track__composer text-muted">{{$piece->composer->short_name}}</span></div>
        <span class="playlist-track__duration text-muted" data-track-duration aria-label="Duration">—</span>
        @auth('web')
        <button class="btn-raw text-danger playlist-track__favorite" type="button" data-playlist-favorite data-favorited="{{isset($folder) || ($piece->webapp_playlist_favorited ?? 0) ? 'true' : 'false'}}"
            data-url="{{route('webapp.users.favorites.update', array_filter(['piece' => $piece->id, 'folder_id' => isset($folder) ? $folder->id : null]))}}"
            aria-pressed="{{isset($folder) || ($piece->webapp_playlist_favorited ?? 0) ? 'true' : 'false'}}" aria-label="{{isset($folder) ? 'Remove from folder' : 'Favorite'}} {{$piece->short_name}}">
            @icon('heart', ['mr' => 0, 'filled' => isset($folder) || ($piece->webapp_playlist_favorited ?? 0) > 0])
        </button>
        @else
        <a class="btn-raw text-danger playlist-track__favorite" href="{{route('login')}}" aria-label="Sign in to favorite {{$piece->short_name}}">@icon('heart', ['mr' => 0])</a>
        @endauth
        <a class="btn btn-secondary btn-sm playlist-track__go" href="{{route('webapp.pieces.show', $piece)}}">Go @icon('arrow-right', ['mr' => 0])</a>
        @if($canReorder)
        <button class="btn-raw text-muted playlist-track__handle" type="button" data-track-handle aria-label="Reorder {{$piece->short_name}}; use up and down arrow keys" title="Drag to reorder; use arrow keys with keyboard">@icon('grip-vertical', ['mr' => 0])</button>
        @endif
    </article>
    @empty
    <p class="playlist-empty text-muted">No pieces here yet.</p>
    @endforelse
</div>
<div class="playlist-player border-top border-bottom bg-white" data-playlist-player hidden aria-label="Playlist audio player">
    <div class="playlist-player__inner">
        <div class="playlist-player__identity"><img class="rounded-sm" data-player-artwork src="{{asset('images/webapp/thumbnail.jpg')}}" alt=""><div><span data-player-title>Select a piece</span><span class="text-muted" data-player-composer></span></div></div>
        <div class="playlist-player__timeline text-muted"><span data-elapsed>0:00</span><div class="playlist-player__progress"><input class="form-range" type="range" min="0" max="0" step="0.1" value="0" data-seek aria-label="Playback position" disabled><div class="playlist-player__markers" data-section-markers aria-label="Section markers"></div></div><span data-duration>0:00</span></div>
        <div class="playlist-player__transport">
            <button class="btn-raw" type="button" data-previous aria-label="Previous piece">@icon('skip-back', ['mr' => 0, 'filled' => true])</button>
            <button class="btn btn-secondary btn-sm playlist-player__toggle" type="button" data-player-toggle aria-label="Play" disabled><span data-play-icon>@icon('play', ['mr' => 0, 'filled' => true])</span><span data-pause-icon hidden>@icon('pause', ['mr' => 0, 'filled' => true])</span></button>
            <button class="btn-raw" type="button" data-next aria-label="Next piece">@icon('skip-forward', ['mr' => 0, 'filled' => true])</button>
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
    @include('webapp.playlist.sections')
</div>
@include('webapp.piece.components.upgrade')
