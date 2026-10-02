<section class="piece-moments mt-3" data-moments-for="{{$videoId}}" aria-label="Sections in this piece">
    
    <div class="piece-moments__list" @if(empty($splitMoments)) style="max-height: 137px; overflow-y: scroll;" @endif>
        @foreach($moments as $moment)
        @php($seconds = (int) floor($moment['start_time']))
        <button type="button" class="piece-moments__row" data-moment-id="{{$moment['id']}}">
            <span class="piece-moments__time">{{$seconds >= 3600 ? sprintf('%d:%02d:%02d', floor($seconds / 3600), floor(($seconds % 3600) / 60), $seconds % 60) : sprintf('%d:%02d', floor($seconds / 60), $seconds % 60)}}</span>
            <span>{{$moment['title']}}</span>
        </button>
        @endforeach
    </div>
</section>
