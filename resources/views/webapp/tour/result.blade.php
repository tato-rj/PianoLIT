<article class="match-result">
    <h3 class="text-center mb-4" tabindex="-1">Your match</h3>
    <div class="match-result-art rounded-top" style="background-image: url('{{ $piece->image_background ?? optional($piece->period)->cover_image }}')">
        <img src="{{ $piece->composer->cover_image }}" class="rounded-circle border border-white shadow" alt="{{ $piece->composer->name }}">
    </div>
    <div class="p-4 border rounded-bottom">
        <h4>{{ $piece->medium_name }}</h4>
        <p class="text-muted">by {{ $piece->composer->name }}</p>
        <div class="mb-4" style="white-space: pre-wrap">{{ $piece->description }}</div>
        @php($video = $piece->tutorials->firstWhere('type', 'performance') ?? $piece->tutorials->first())
        @if($video)
        <video class="w-100" controls playsinline preload="metadata" data-result-media poster="{{ $piece->image_background ?? optional($piece->period)->cover_image }}">
            <source src="{{ $video->video_url }}" type="video/mp4">
        </video>
        @elseif($piece->audio_path)
        <audio class="w-100" controls preload="metadata" data-result-media src="{{ storage($piece->audio_path) }}"></audio>
        @endif
        <p class="small text-muted mt-2">A short preview of your match.</p>
        <div class="text-center mt-4"><a href="{{ route('webapp.pieces.show', $piece) }}" class="btn btn-primary rounded-pill">Learn more about this piece</a></div>
    </div>
</article>
