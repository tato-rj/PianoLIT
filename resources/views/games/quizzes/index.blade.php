@extends('layouts.app', ['title' => 'Quizzes | PianoLIT Learn', 'bodyClass' => 'learn-home quiz-page'])

@section('content')
@php
    $selectedTopics = array_values(array_filter((array) (is_array(request('topics')) ? request('topics') : explode(' ', (string) request('topics', ''))), function ($value) { return is_string($value) && trim($value) !== ''; }));
    $selectedLevel = collect((array) request('level'))->first(function ($value) { return is_string($value) && trim($value) !== ''; });
    $levelQuery = $selectedLevel ? ['level' => $selectedLevel] : [];
    $topicLinks = [['label' => 'All topics', 'url' => route('quizzes.index', $levelQuery), 'active' => !$selectedTopics]];
    foreach ($topics as $topic) {
        $topicLinks[] = ['label' => ucfirst($topic->name), 'url' => route('quizzes.index', array_merge($levelQuery, ['topics' => $topic->slug])), 'active' => in_array($topic->slug, $selectedTopics, true)];
    }
    $levelChoices = ['' => 'All levels'];
    foreach ($levels as $level) $levelChoices[$level->slug] = ucfirst($level->name);
@endphp
<div class="container py-4 pb-5">
    @include('components.breadcrumb', ['items' => [
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'Learn', 'url' => route('learn.home')],
        ['label' => 'Quizzes'],
    ]])
    @component('components.hub-header', ['heading' => 'Quizzes', 'preview' => false])
        A few questions. A new discovery.<br class="d-none d-md-block"> Test your musical knowledge and learn something along the way.
    @endcomponent

    @include('components.topic-navigation', ['items' => $topicLinks, 'label' => 'Quiz topics'])

    @component('components.editorial-layout')
        <div class="row g-3 align-items-end mb-4">
            <div class="col-md">
                <h2 class="h5 mb-2" id="quiz-directory-title">Find your next challenge</h2>
                <p class="small text-muted mb-0">{{ $quizzes->isNotEmpty() ? 'Showing '.$quizzes->firstItem().'–'.$quizzes->lastItem().' of '.$quizzes->total() : number_format($quizzes->total()) }} {{ Str::plural('quiz', $quizzes->total()) }}</p>
            </div>
            <div class="col-sm-auto">
                <form method="GET" action="{{ route('quizzes.index') }}" class="d-flex flex-wrap align-items-end gap-2">
                    @foreach($selectedTopics as $slug)<input type="hidden" name="topics[]" value="{{ $slug }}">@endforeach
                    @select(['bag' => 'default', 'name' => 'level', 'id' => 'quiz-level', 'label' => 'Difficulty', 'choices' => $levelChoices, 'value' => $selectedLevel, 'required' => false, 'grid' => 'mb-0'])
                    <button class="btn btn-secondary" type="submit">Apply</button>
                </form>
            </div>
        </div>
        <section aria-labelledby="quiz-directory-title">
            @if($quizzes->isNotEmpty())
                <div class="row g-4">
                    @foreach($quizzes as $quiz)
                        <div class="col-md-6">@include('games.quizzes.card', ['cardId' => 'quiz-'.$quiz->id])</div>
                    @endforeach
                </div>
                @if($quizzes->hasPages())<div class="mt-5">{{ $quizzes->onEachSide(1)->links() }}</div>@endif
            @else
                <div class="py-4">
                    <h3 class="h5">{{ $selectedTopics || $selectedLevel ? 'No quizzes match these filters' : 'New quizzes are on their way' }}</h3>
                    <p class="text-muted">{{ $selectedTopics || $selectedLevel ? 'Try another topic or level, or browse all quizzes.' : 'Explore our other learning resources while you wait.' }}</p>
                    <a class="btn btn-secondary" href="{{ $selectedTopics || $selectedLevel ? route('quizzes.index') : route('learn.home') }}">{{ $selectedTopics || $selectedLevel ? 'View all quizzes' : 'Explore Learn' }} @icon('arrow-right', ['mr' => 0, 'ml' => 2])</a>
                </div>
            @endif
        </section>
        @slot('sidebar')
            @include('components.display.ads', ['ads' => ['crashcourse', 'ebook', 'escore'], 'variant' => 'cards'])
        @endslot
    @endcomponent
</div>
@endsection
