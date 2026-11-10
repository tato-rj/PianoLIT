<div class="mb-3" data-question-media>
    <input type="hidden" name="question_media[{{ $questionIndex }}]" value="{{ $mediaValue }}" data-question-media-value>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <button type="button" class="btn btn-secondary btn-sm" data-choose-question-media="audio" data-url="{{ route('admin.quizzes.media.choose', 'audio') }}">@icon('volume-2')Choose audio</button>
        <button type="button" class="btn btn-secondary btn-sm" data-choose-question-media="images" data-url="{{ route('admin.quizzes.media.choose', 'images') }}">@icon('image')Choose image</button>
        <button type="button" class="btn btn-secondary btn-sm" data-remove-question-media @unless($mediaSelection) hidden @endunless>@icon('x')Remove media</button>
    </div>
    <div class="mt-2" data-question-media-preview @unless($mediaSelection) hidden @endunless>
        <p class="small text-muted mb-2 text-break" data-question-media-name>{{ $mediaSelection['name'] ?? '' }}</p>
        <img width="160" height="100" class="rounded object-fit-contain" alt="{{ $mediaSelection['name'] ?? '' }}" data-question-media-image @if($mediaSelection && $mediaSelection['type'] === 'images' && $mediaSelection['url']) src="{{ $mediaSelection['url'] }}" @else hidden @endif>
        <audio controls preload="none" class="w-100" data-question-media-audio aria-label="Question audio" @if($mediaSelection && $mediaSelection['type'] === 'audio' && $mediaSelection['url']) src="{{ $mediaSelection['url'] }}" @else hidden @endif></audio>
        <p class="small text-muted mb-0" data-question-media-missing @if(!$mediaSelection || $mediaSelection['url']) hidden @endif>File unavailable. Choose another file or remove the media.</p>
    </div>
    @include('components.forms.error', ['field' => 'question_media.'.$questionIndex, 'errorClasses' => 'd-block'])
</div>
