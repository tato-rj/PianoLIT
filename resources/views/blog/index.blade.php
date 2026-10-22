@extends('layouts.app', ['title' => 'PianoLIT Blog', 'bodyClass' => 'blog-home'])

@section('content')
@php
    $selectedTopics = is_array(request('topics')) ? request('topics') : explode(' ', (string) request('topics', ''));
    $selectedTopics = array_filter($selectedTopics, function ($slug) { return is_string($slug) && trim($slug) !== ''; });
    $activeTopic = $topics->first(function ($topic) use ($selectedTopics) { return in_array($topic->slug, $selectedTopics, true); });
@endphp

<div class="container py-4">
    @include('components.breadcrumb', ['items' => [
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'Blog'],
    ]])

    <header class="blog-home__intro row align-items-end gy-4 mb-4">
        <div class="col-lg-8">
            <h1 class="mb-3">PianoLIT Blog</h1>
            <p class="blog-home__description text-muted mb-0">Stories, ideas, and discoveries from the world of piano.<br class="d-none d-md-block">A little inspiration for wherever music takes you.</p>
        </div>
        <div class="col-lg-4 text-lg-end">
            @include('components.newsletter.subscribe-button', ['label' => 'Join the newsletter', 'preview' => false])
        </div>
    </header>

    <nav class="blog-home__topics d-flex flex-wrap gap-2 py-4 mb-4 border-top border-bottom" aria-label="Article topics">
        <a href="{{ route('posts.index') }}" class="btn btn-sm btn-secondary" @if(empty($selectedTopics)) aria-current="page" @endif>All stories</a>
        @foreach($topics as $topic)
            <a href="{{ route('posts.index', ['topics' => $topic->slug]) }}" class="btn btn-sm {{ in_array($topic->slug, $selectedTopics, true) ? 'btn-primary' : 'btn-secondary' }}" @if(in_array($topic->slug, $selectedTopics, true)) aria-current="page" @endif>{{ ucfirst($topic->name) }}</a>
        @endforeach
    </nav>

    @component('components.editorial-layout')
        @if($posts->isNotEmpty())
            <section aria-labelledby="blog-stories-title">
                <div class="d-flex flex-wrap align-items-baseline justify-content-between gap-2 mb-4">
                    <h2 id="blog-stories-title" class="h4 mb-0">{{ $activeTopic ? ucfirst($activeTopic->name) : 'Articles' }}</h2>
                    <p class="text-muted small mb-0">Showing {{ $posts->firstItem() }}–{{ $posts->lastItem() }} of {{ $posts->total() }} stories</p>
                </div>
                <div class="row g-4">
                    @foreach($posts as $post)
                        <div class="col-md-6 col-xl-4">
                            @include('blog.components.story-card', ['post' => $post])
                        </div>
                    @endforeach
                </div>
            </section>

            @if($posts->hasPages())
                <div class="mt-5">{{ $posts->onEachSide(1)->links() }}</div>
            @endif
        @else
            <section class="rounded bg-light p-4 p-md-5" aria-labelledby="blog-empty-title">
                <h2 id="blog-empty-title" class="h4">{{ $posts->total() ? 'You’ve reached the end of these stories.' : 'No stories here just yet.' }}</h2>
                <p class="text-muted">{{ $posts->total() ? 'Head back to the beginning for more reading.' : 'Explore another topic or come back soon for something new.' }}</p>
                <a href="{{ route('posts.index') }}" class="btn btn-primary">Browse all stories @icon('arrow-right', ['ml' => 2, 'mr' => 0])</a>
            </section>
        @endif

        @slot('sidebar')
            <div class="mb-4">
                <h2 class="h5 mb-2">Explore PianoLIT</h2>
                <p class="text-muted small mb-0">Keep your curiosity playing.</p>
            </div>
            @include('components.display.ads', ['ads' => ['crashcourse', 'ebook', 'escore'], 'variant' => 'cards'])
        @endslot
    @endcomponent
</div>
@endsection
