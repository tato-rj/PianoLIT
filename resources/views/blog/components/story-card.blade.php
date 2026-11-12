@php
    $articleDate = $post->published_at ?? $post->created_at;
@endphp
<article class="blog-story card card-lift rounded border-0 shadow-light h-100">
    <a href="{{ route('posts.show', $post->slug) }}" class="blog-story__link h-100">
        <div class="blog-story__image">
            <img src="{{ $post->cover_path ? $post->cover_image() : mix('images/home/projects/blog.svg') }}" alt="" width="800" height="500" loading="lazy" decoding="async">
        </div>
        <div class="blog-story__body">
            <p class="blog-story__meta d-flex flex-wrap align-items-center gap-2 text-muted mb-2">
                <span class="d-inline-flex align-items-center">@icon('clock', ['mr' => 1]){{ $post->reading_time }} min read</span>
                @include('blog.components.view-count', ['count' => $postViews[$post->id] ?? null])
            </p>
            <h3 class="blog-story__title">{{ $post->title }}</h3>
            <p class="blog-story__description text-muted">{{ $post->description }}</p>
            <div class="blog-story__details w-100 pt-3">
                @if($post->topics->isNotEmpty())
                    <ul class="d-flex flex-wrap gap-1 list-unstyled mb-2" aria-label="Story tags">
                        @foreach($post->topics as $topic)
                            <li>@pill(['label' => e(ucfirst($topic->name)), 'theme' => 'grey'])</li>
                        @endforeach
                    </ul>
                @endif
                <time class="d-block text-muted" datetime="{{ $articleDate->toDateString() }}">{{ $articleDate->format('M j, Y') }}</time>
            </div>
        </div>
    </a>
</article>
