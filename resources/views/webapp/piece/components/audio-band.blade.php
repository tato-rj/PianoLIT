<div data-piece-player-page>
    <div data-playlist-tracks hidden>
        @php($audioMoments = collect(optional($piece->tutorials->first())->listeningMoments() ?: [])->map(function ($moment) { return \Illuminate\Support\Arr::except($moment, ['comment']); })->all())
        <div data-track data-title="{{$piece->short_name}}" data-composer="{{$piece->composer->short_name}}"
            data-audio="{{$piece->audio}}" data-preview="{{$hasMediaAccess ? 0 : $previewSeconds}}"
            data-audio-moments="{{json_encode($audioMoments)}}"
            data-artwork="{{$piece->cover_path ? storage($piece->cover_path) : asset(optional($piece->period)->cover_image ?: 'images/webapp/thumbnail.jpg')}}"></div>
    </div>
    @include('webapp.playlist.player', ['piecePlayer' => true])
</div>
