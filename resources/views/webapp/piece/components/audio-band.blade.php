<div data-piece-player-page>
    <div data-playlist-tracks hidden>
        @php($audioMoments = collect(optional($piece->tutorials->first())->listeningMoments() ?: [])->map(function ($moment) { return \Illuminate\Support\Arr::except($moment, ['comment']); })->all())
        <div data-track data-access-prompt="{{ app(\App\Services\WebApp\AccessPolicy::class)->promptTarget(auth('web')->user(), 'audio', $piece) }}" data-title="{{$piece->short_name}}" data-composer="{{$piece->composer->short_name}}"
            data-audio="{{$piece->audio}}" data-preview="{{$hasAudioAccess ? 0 : $audioPreviewSeconds}}"
            @if($audioPreviewSeconds === 0) data-audio-blocked="true" @endif
            data-audio-moments="{{json_encode($audioMoments)}}"
            data-artwork="{{$piece->web_image_background}}" data-piece-id="{{$piece->id}}"></div>
    </div>
    @include('webapp.playlist.player', ['piecePlayer' => true])
</div>
