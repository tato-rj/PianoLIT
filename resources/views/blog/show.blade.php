@extends('layouts.app', ['title' => $post->title . ' | PianoLIT Blog',
	'bodyClass' => 'blog-article',
	'popup' => ['view' => 'gift'],
	'shareable' => [
		'keywords' => '',
		'title' => $post->title,
		'description' => $post->description,
		'thumbnail' => $post->thumbnail_image(),
		'created_at' => $post->created_at->format(DateTime::ISO8601),
		'updated_at' => $post->updated_at->format(DateTime::ISO8601)
	]])

@push('header')
<script async defer data-pin-hover="true" data-pin-tall="true" src="//assets.pinterest.com/js/pinit.js"></script>
@endpush

@section('content')

@progressbar

<div id="blog-post" class="container py-4 pb-5">
    @include('components.navigation.breadcrumb', ['items' => [
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'Blog', 'url' => route('posts.index')],
        ['label' => $post->title],
    ]])

    @component('components.layout.editorial')
        @if(! empty($preview))
            <div class="alert alert-warning mb-4" role="alert">
                @icon('triangle-alert', ['mr' => 2])This post is <u>not published</u>. Only admins can see this page.
            </div>
        @endif

        <article id="main-content" aria-labelledby="article-title">
            <header class="mb-4">
                @if($post->topics->isNotEmpty())
                    <nav class="d-flex flex-wrap gap-2 mb-3" aria-label="Story tags">
                        @foreach($post->topics as $topic)
                            <a href="{{ route('posts.index', ['topics' => $topic->slug]) }}" class="link-none">@pill(['label' => e(ucfirst($topic->name)), 'theme' => 'grey'])</a>
                        @endforeach
                    </nav>
                @endif
                <h1 id="article-title" class="blog-article__title mb-4">{{ $post->title }}</h1>
                <p class="blog-article__description text-muted mb-4">{{ $post->description }}</p>
                <div class="blog-article__meta d-flex flex-wrap align-items-center gap-3 text-muted">
                    <span class="fw-semibold text-dark">PianoLIT Team</span>
                    <time datetime="{{ $post->created_at->toDateString() }}">{{ $post->created_at->format('M j, Y') }}</time>
                    <span class="d-inline-flex align-items-center">@icon('clock', ['mr' => 1]){{ $post->reading_time }} min read</span>
                </div>
            </header>

            <figure class="mb-4 mb-md-5">
                <img src="{{ $post->cover_path ? $post->cover_image() : mix('images/home/projects/blog.svg') }}" alt="" class="blog-article__cover w-100 rounded-sm" decoding="async" fetchpriority="high">
                @if($post->cover_credits)
                    <figcaption class="small text-muted mt-2">{{ $post->cover_credits }}</figcaption>
                @endif
            </figure>

            <div id="blog-content" class="blog-article__content">
                {!! $post->content !!}
            </div>

            @if($post->references)
                <section class="blog-article__references border-top mt-4 pt-4 text-muted" aria-labelledby="article-references-title">
                    <h2 id="article-references-title" class="h6 mb-3">References</h2>
                    <ul class="small ps-3 mb-0">
                        @foreach($post->referencesArray as $reference)
                            <li class="mb-2">{{ $reference }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </article>

        <div class="mt-4 mt-md-5">
            @include('components.newsletter.invitation', ['headingId' => 'article-newsletter-title', 'preview' => false])
        </div>

        @if($suggestions->isNotEmpty())
            <section class="mt-5 pt-md-3" aria-labelledby="article-more-title">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                    <h2 id="article-more-title" class="h4 mb-0">Keep reading</h2>
                    <a href="{{ route('posts.index') }}" class="btn btn-secondary btn-sm">All stories @icon('arrow-right', ['mr' => 0, 'ml' => 2])</a>
                </div>
                <div class="row g-4">
                    @foreach($suggestions as $suggestion)
                        <div class="col-md-6 col-xl-4">
                            @include('blog.components.story-card', ['post' => $suggestion])
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @slot('sidebar')
            @include('components.promotions.ads', ['ads' => ['crashcourse', 'ebook', 'escore'], 'variant' => 'cards'])
        @endslot
    @endcomponent
</div>

@if($post->hasGift())
<div id="gift-container" data-url="{{route('posts.gift', $post)}}"></div>
@endif
@endsection

@push('scripts')
@addthis

<script type="text/javascript">
$(document).ready(function() {
	showScrollProgressBar($('#main-content'));
});

@if($post->hasGift())
loadPopup($('#gift-container'));
@endif
</script>
@endpush
