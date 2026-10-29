@extends('layouts.app', [
    'title' => rtrim($quiz->title, '. ').' | PianoLIT Learn', 'bodyClass' => 'learn-home quiz-page',
    'shareable' => [
        'keywords' => 'music quiz,piano,music theory', 'title' => rtrim($quiz->title, '. '),
        'description' => $quiz->description, 'thumbnail' => $quiz->thumbnail_image(),
        'created_at' => $quiz->created_at->format(DateTime::ISO8601), 'updated_at' => $quiz->updated_at->format(DateTime::ISO8601),
    ],
])

@section('content')
<div class="container py-4 pb-5">
    @include('components.navigation.breadcrumb', ['items' => [
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'Learn', 'url' => route('learn.home')],
        ['label' => 'Quizzes', 'url' => route('quizzes.index')],
        ['label' => rtrim($quiz->title, '. ')],
    ]])
    @component('components.layout.editorial')
        <article id="quiz" data-url="{{ route('quizzes.feedback', $quiz->slug) }}" data-quiz>
            @if(!empty($preview))
                <div class="alert alert-secondary mb-4" role="status">@icon('eye')Admin preview · This quiz has not been published</div>
            @endif
            <header class="mb-4">
                @if($quiz->topics->isNotEmpty())
                    <div class="d-flex flex-wrap gap-2 mb-3" aria-label="Quiz topics">
                        @foreach($quiz->topics as $topic)<a class="btn btn-secondary btn-sm" href="{{ route('quizzes.index', ['topics' => $topic->slug]) }}">{{ ucfirst($topic->name) }}</a>@endforeach
                    </div>
                @endif
                <h1 class="h2 mb-3">{{ rtrim($quiz->title, '. ') }}</h1>
                <p class="text-muted mb-4">{{ $quiz->description }}</p>
                <div class="d-flex flex-wrap align-items-center gap-3 small text-muted">
                    <span>@icon('list'){{ count($quiz->questions) }} {{ Str::plural('question', count($quiz->questions)) }}</span>
                    <span>@icon('clock')About {{ $quiz->duration }}</span>
                    @include('games.quizzes.level')
                </div>
            </header>
            @if($quiz->cover_path)
                <img src="{{ $quiz->cover_image() }}" alt="{{ rtrim($quiz->title, '. ') }}" class="quiz-cover w-100 d-block mb-4" decoding="async">
            @endif

            <section class="quiz-start bg-white border rounded-sm p-4 mb-4" data-quiz-introduction aria-labelledby="quiz-start-title">
                <h2 class="h5 mb-2" id="quiz-start-title">{{ count($quiz->questions) ? 'Ready to give it a try?' : 'This quiz is being prepared' }}</h2>
                @if(count($quiz->questions))
                    <p class="text-muted mb-3">Trust your brain. Or your lucky finger.</p>
                    @if($quiz->results_count > 2)<p class="small text-muted mb-3">Average score: <strong>{{ $quiz->average_score }} out of {{ count($quiz->questions) }}</strong> · {{ number_format($quiz->results_count) }} attempts</p>@endif
                    <button class="btn btn-primary" type="button" id="start-quiz" data-quiz-start>Start quiz @icon('arrow-right', ['mr' => 0, 'ml' => 2])</button>
                    <noscript><p class="small text-muted mt-3 mb-0">Enable JavaScript to answer this quiz.</p></noscript>
                @else
                    <p class="text-muted mb-0">Explore another quiz while we get this one ready.</p>
                @endif
            </section>

            <div id="quiz-content" hidden data-quiz-content>
                <div class="mb-4" data-quiz-progress>
                    <div class="d-flex justify-content-between gap-3 small mb-2"><span>Your progress <strong class="quiz-streak ml-2" data-quiz-streak hidden></strong></span><span data-quiz-progress-label>0 / {{ count($quiz->questions) }}</span></div>
                    <div class="progress quiz-progress" role="progressbar" aria-label="Questions answered" aria-valuemin="0" aria-valuemax="{{ count($quiz->questions) }}" aria-valuenow="0"><div class="progress-bar" style="width: 0%"></div></div>
                </div>
                @include('games.quizzes.questions')
                <div class="d-flex justify-content-end mb-4"><button type="button" class="btn btn-primary" data-quiz-next hidden>Next →</button></div>
                <section class="quiz-results rounded-sm p-4 mb-4" data-quiz-results hidden tabindex="-1" aria-label="Quiz result" aria-busy="false">
                    <div data-quiz-result-status role="status" aria-live="polite"></div>
                    <div id="game-feedback" data-quiz-feedback aria-live="polite" aria-atomic="true"></div>
                    <button type="button" class="btn btn-primary mt-3" data-quiz-retry hidden>Retry</button>
                    <div class="mt-4" data-quiz-result-actions hidden><div class="d-flex justify-content-center flex-wrap gap-2">
                        <button type="button" class="btn btn-primary" data-quiz-reset>@icon('rotate-ccw')Play again</button>
                        <button type="button" class="btn btn-secondary" data-quiz-review>Review answers</button>
                        <a class="btn btn-secondary" href="{{ route('quizzes.index') }}">More quizzes @icon('arrow-right', ['mr' => 0, 'ml' => 2])</a>
                    </div></div>
                </section>
            </div>
        </article>
        @if($suggestions->isNotEmpty())
            <section class="border-top pt-4 mt-5" aria-labelledby="more-quizzes-title">
                <h2 class="h4 mb-4" id="more-quizzes-title">Keep your curiosity going</h2>
                <div class="row g-4">
                    @foreach($suggestions as $suggestion)
                        <div class="col-md-6">@include('games.quizzes.card', ['quiz' => $suggestion, 'cardId' => 'suggested-quiz-'.$suggestion->id])</div>
                    @endforeach
                </div>
            </section>
        @endif
        <div class="mt-5">@include('components.newsletter.invitation', ['headingId' => 'quiz-newsletter-title', 'preview' => false])</div>
        @slot('sidebar')
            @include('components.promotions.ads', ['ads' => ['crashcourse', 'ebook', 'escore'], 'variant' => 'cards'])
        @endslot
    @endcomponent
</div>
@endsection

@push('scripts')
<script src="{{ mix('js/views/quizzes.js') }}" defer></script>
@endpush
