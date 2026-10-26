@extends('layouts.app', ['title' => 'PianoLIT Blog', 'bodyClass' => 'blog-home'])

@section('content')
@php
    $selectedTopics = is_array(request('topics')) ? request('topics') : explode(' ', (string) request('topics', ''));
    $selectedTopics = array_filter($selectedTopics, function ($slug) { return is_string($slug) && trim($slug) !== ''; });
    $activeTopic = $topics->first(function ($topic) use ($selectedTopics) { return in_array($topic->slug, $selectedTopics, true); });
@endphp

<div class="container py-4">
    @include('components.navigation.breadcrumb', ['items' => [
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'Blog'],
    ]])

    @component('components.layout.hub-header', ['heading' => 'PianoLIT Blog', 'preview' => false])
        Stories, ideas, and discoveries from the world of piano.<br class="d-none d-md-block">A little inspiration for wherever music takes you.
    @endcomponent

    @php
        $topicLinks = [['label' => 'All stories', 'url' => route('posts.index'), 'active' => empty($selectedTopics), 'variant' => 'secondary']];
        foreach ($topics as $topic) {
            $topicLinks[] = ['label' => ucfirst($topic->name), 'url' => route('posts.index', ['topics' => $topic->slug]), 'active' => in_array($topic->slug, $selectedTopics, true)];
        }
    @endphp
    @include('components.navigation.topic-navigation', ['items' => $topicLinks, 'label' => 'Article topics', 'class' => 'blog-home__topics'])

    @component('components.layout.editorial')
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
            @include('components.promotions.ads', ['ads' => ['crashcourse', 'ebook', 'escore'], 'variant' => 'cards'])
        @endslot
    @endcomponent
</div>
@endsection
