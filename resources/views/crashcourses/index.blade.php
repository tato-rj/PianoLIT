@extends('layouts.app', ['title' => 'Crash courses | PianoLIT Learn', 'bodyClass' => 'learn-home'])

@section('content')
@php
    $selectedTopics = array_values(array_filter(is_array(request('topics')) ? request('topics') : explode(' ', (string) request('topics', '')), function ($topic) { return is_string($topic) && trim($topic) !== ''; }));
    $topicLinks = [['label' => 'All courses', 'url' => route('crashcourses.index'), 'active' => empty($selectedTopics)]];
    foreach ($topics as $topic) {
        $topicLinks[] = ['label' => ucfirst($topic->name), 'url' => route('crashcourses.index', ['topics' => $topic->slug]), 'active' => in_array($topic->slug, $selectedTopics, true)];
    }
@endphp
<div class="container py-4 pb-5">
    @include('components.breadcrumb', ['items' => [
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'Learn', 'url' => route('learn.home')],
        ['label' => 'Crash courses'],
    ]])
    @component('components.hub-header', ['heading' => 'Crash courses', 'preview' => false])
        Make room for a little discovery each day.<br class="d-none d-md-block"> Free music lessons, delivered one at a time to your inbox.
    @endcomponent

    @include('components.topic-navigation', ['items' => $topicLinks, 'label' => 'Course topics'])

    @component('components.editorial-layout')
        @if($crashcourses->isNotEmpty())
            <section aria-labelledby="crashcourses-directory-title">
                <div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2 mb-4">
                    <h2 id="crashcourses-directory-title" class="h5 mb-0">Find your next lesson</h2>
                    <p class="small text-muted mb-0">Showing {{ $crashcourses->firstItem() }}–{{ $crashcourses->lastItem() }} of {{ $crashcourses->total() }} {{ Str::plural('course', $crashcourses->total()) }}</p>
                </div>
                <div class="row g-4">
                    @foreach($crashcourses as $crashcourse)
                        <div class="col-md-6">@include('crashcourses.course')</div>
                    @endforeach
                </div>
                @if($crashcourses->hasPages())
                    <div class="mt-5">{{ $crashcourses->onEachSide(1)->links() }}</div>
                @endif
            </section>
        @else
            <section class="py-4" aria-labelledby="crashcourses-empty-title">
                <h2 id="crashcourses-empty-title" class="h5">{{ $selectedTopics ? 'No courses match these topics' : 'New courses are on their way' }}</h2>
                <p class="text-muted">{{ $selectedTopics ? 'Try another topic or browse all of our email courses.' : 'Explore our other learning tools while you wait.' }}</p>
                <a class="btn btn-secondary" href="{{ $selectedTopics ? route('crashcourses.index') : route('learn.home') }}">{{ $selectedTopics ? 'View all courses' : 'Explore Learn' }} @icon('arrow-right', ['mr' => 0, 'ml' => 2])</a>
            </section>
        @endif
        @slot('sidebar')
            @include('components.display.ads', ['ads' => ['ebook', 'escore'], 'variant' => 'cards'])
        @endslot
    @endcomponent
</div>
@endsection
