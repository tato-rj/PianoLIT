@extends('layouts.app', [
    'title' => $share['title'].' | PianoLIT', 'bodyClass' => 'learn-home quiz-page',
    'shareable' => [
        'keywords' => 'music quiz,piano,music theory', 'title' => $share['title'],
        'description' => $share['challenge'].' Play '.$share['quiz_title'].' on PianoLIT.',
        'thumbnail' => $share['image'], 'image_width' => 1200, 'image_height' => 630,
        'card' => 'summary_large_image', 'url' => $share['url'],
        'created_at' => $quiz->created_at->format(DateTime::ISO8601), 'updated_at' => $quiz->updated_at->format(DateTime::ISO8601),
    ],
])

@section('content')
<div class="container py-4 pb-5">
    @include('components.navigation.breadcrumb', ['items' => [
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'Learn', 'url' => route('learn.home')],
        ['label' => 'Quizzes', 'url' => route('quizzes.index')],
        ['label' => $share['quiz_title'], 'url' => route('quizzes.show', $quiz->slug)],
        ['label' => 'The challenge'],
    ]])
    <section class="quiz-shared-result text-center mx-auto py-4">
        <p class="text-muted mb-2">A music quiz challenge for you</p>
        <h1 class="h2 mb-4">{{ $share['score'] }} / {{ $share['total'] }}. {{ $share['challenge'] }}</h1>
        <a href="{{ route('quizzes.show', $quiz->slug) }}" class="d-block mb-4" aria-label="Play {{ $share['quiz_title'] }}">
            <img src="{{ $share['image'] }}" alt="{{ $share['quiz_title'] }} — {{ $share['score'] }} out of {{ $share['total'] }}" class="quiz-share-card w-100" width="1200" height="630">
        </a>
        <a class="btn btn-primary btn-lg" href="{{ route('quizzes.show', $quiz->slug) }}">@icon('play')Take the quiz</a>
    </section>
</div>
@endsection
