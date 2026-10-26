@component('components.resource-card', [
    'id' => $cardId ?? 'quiz-'.$quiz->id, 'title' => rtrim($quiz->title, '. '),
    'description' => Str::limit($quiz->description, 180),
    'format' => ($quiz->level ? ucfirst($quiz->level->name).' · ' : '').count($quiz->questions).' '.Str::plural('question', count($quiz->questions)).' · '.$quiz->duration,
    'url' => route('quizzes.show', $quiz->slug), 'action' => 'Try this quiz', 'artworkClass' => 'resource-card__art--cover',
])
    @slot('artwork')
        @if($quiz->cover_path)
            <img src="{{ $quiz->cover_image() }}" alt="" loading="lazy" decoding="async">
        @else
            @include('learn.components.artwork', ['kind' => 'quizzes'])
        @endif
    @endslot
@endcomponent
