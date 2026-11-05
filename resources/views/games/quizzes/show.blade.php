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
                <p class="text-muted mb-0">{{ $quiz->description }}</p>
            </header>
            <div class="quiz-launcher" data-quiz-introduction>
                <div class="quiz-launcher-art" aria-hidden="true">
                    @if($quiz->cover_path)
                        <img src="{{ $quiz->cover_image() }}" alt="" decoding="async">
                    @else
                        @include('learn.components.artwork', ['kind' => 'quizzes'])
                    @endif
                </div>
                @if(count($quiz->questions))
                    <span class="visually-hidden" id="quiz-start-description">Answer each question, then see your score.</span>
                    @include('components.media.play-button', [
                        'id' => 'start-quiz', 'label' => 'Start quiz', 'modalId' => 'quiz-result-modal',
                        'descriptionId' => 'quiz-start-description', 'data' => ['quiz-start' => ''], 'labelData' => ['quiz-start-label' => ''],
                    ])
                @else
                    <p class="text-white text-center p-4 mb-0">This quiz is being prepared</p>
                @endif
            </div>
            <noscript><p class="small text-muted mt-3 mb-0">Enable JavaScript to answer this quiz.</p></noscript>
            <dl class="row row-cols-2 row-cols-md-3 g-3 mt-2 mb-0" aria-label="Quiz statistics">
                <div class="col"><dt class="small text-muted fw-normal">Created</dt><dd class="mb-0">@if($quiz->created_at)<time datetime="{{ $quiz->created_at->toDateString() }}">{{ $quiz->created_at->format('M j, Y') }}</time>@else Not recorded @endif</dd></div>
                <div class="col"><dt class="small text-muted fw-normal">Questions</dt><dd class="mb-0">{{ count($quiz->questions) }}</dd></div>
                <div class="col"><dt class="small text-muted fw-normal">Time</dt><dd class="mb-0">About {{ $quiz->duration }}</dd></div>
                <div class="col"><dt class="small text-muted fw-normal">Times answered</dt><dd class="mb-0">{{ number_format($statistics['plays']) }}</dd></div>
                <div class="col"><dt class="small text-muted fw-normal">Average score</dt><dd class="mb-0">{{ $statistics['average_label'] !== null ? $statistics['average_label'].' / '.count($quiz->questions) : ($statistics['plays'] ? 'Not available' : 'No scores yet') }}</dd></div>
                @if($quiz->level)<div class="col"><dt class="small text-muted fw-normal">Difficulty</dt><dd class="mb-0">{{ ucfirst($quiz->level->name) }}</dd></div>@endif
            </dl>

            @component('components.dialogs.modal', ['id' => 'quiz-result-modal', 'titleId' => 'quiz-dialog-title', 'centered' => true, 'data' => ['quiz-modal' => ''], 'options' => ['size' => 'lg', 'header' => ['raw' => true], 'body' => ['padding' => 0]]])
            @slot('header')
                <span id="quiz-dialog-title" class="visually-hidden">{{ rtrim($quiz->title, '. ') }}</span>
                <div class="quiz-progress" role="progressbar" aria-label="Questions answered" aria-valuemin="0" aria-valuemax="{{ count($quiz->questions) }}" aria-valuenow="0">
                    @foreach($quiz->questions as $question)
                        <span class="quiz-progress-step" data-quiz-progress-step aria-hidden="true"></span>
                    @endforeach
                </div>
            @endslot
            @slot('body')
                <div id="quiz-content" hidden data-quiz-content>
                    <div class="d-flex justify-content-between align-items-center gap-3 small text-muted mb-4">
                        <span>Your progress <span data-quiz-progress-label>0 / {{ count($quiz->questions) }}</span></span>
                        <strong class="quiz-streak" data-quiz-streak hidden></strong>
                    </div>
                    @include('games.quizzes.questions')
                    <div class="quiz-next d-flex justify-content-end gap-2 mt-auto pt-3">
                        <button type="button" class="btn btn-secondary" data-quiz-back disabled>Back</button>
                        <button type="button" class="btn btn-primary" data-quiz-next disabled>Next @icon('arrow-right', ['mr' => 0, 'ml' => 2])</button>
                        <button type="button" class="btn btn-primary" data-quiz-show-result hidden>View result</button>
                    </div>
                </div>
                <section class="quiz-results" data-quiz-results hidden tabindex="-1" aria-label="Quiz result" aria-busy="false">
                    <div class="quiz-reveal mx-auto mb-4" data-quiz-reveal hidden aria-hidden="true">
                        <div class="quiz-reveal-orbit"><span>♪</span><span>✦</span><span>♫</span></div>
                        <span class="quiz-reveal-mystery">?</span>
                    </div>
                    <div data-quiz-result-status role="status" aria-live="polite"></div>
                    <div id="game-feedback" data-quiz-feedback aria-live="polite" aria-atomic="true"></div>
                    <button type="button" class="btn btn-primary mt-3" data-quiz-retry hidden>Retry</button>
                    <div class="mt-3" data-quiz-result-actions hidden><div class="d-flex justify-content-center flex-wrap gap-2">
                        <button type="button" class="btn btn-secondary" data-quiz-reset>@icon('rotate-ccw')Play again</button>
                        <button type="button" class="btn btn-secondary" data-quiz-review>Review answers</button>
                        <a class="btn btn-secondary" href="{{ route('quizzes.index') }}">More quizzes @icon('arrow-right', ['mr' => 0, 'ml' => 2])</a>
                    </div></div>
                </section>
            @endslot
            @endcomponent
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
<script src="{{ mix('js/components/link-share.js') }}" defer></script>
<script src="{{ mix('js/views/quizzes.js') }}" defer></script>
@endpush
