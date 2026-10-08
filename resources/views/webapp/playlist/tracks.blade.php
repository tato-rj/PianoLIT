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
        data-has-score="{{$piece->score_path && $piece->is_public_domain && $hasTrackAccess ? 'true' : 'false'}}" data-piece-id="{{$piece->id}}" data-title="{{$piece->short_name}}" data-composer="{{$piece->composer->short_name}}"
        data-audio="{{$piece->audio_path ? $piece->audio : ''}}" data-preview="{{$hasTrackAccess ? 0 : config('webapp.media_preview_seconds')}}"
        data-audio-moments="{{json_encode($audioMoments)}}"
        data-artwork="{{$piece->web_image_background}}">
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
@include('webapp.playlist.player')
@include('webapp.piece.components.upgrade')
