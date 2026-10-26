@extends('layouts.app', [
    'title' => rtrim($crashcourse->title, '. ').' | PianoLIT Learn',
    'bodyClass' => 'learn-home',
    'shareable' => [
        'keywords' => 'piano,music,crash course,email lessons',
        'title' => rtrim($crashcourse->title, '. '),
        'description' => $crashcourse->description,
        'thumbnail' => $crashcourse->thumbnail_image(),
        'created_at' => $crashcourse->created_at->format(DateTime::ISO8601),
        'updated_at' => $crashcourse->updated_at->format(DateTime::ISO8601),
    ],
])

@section('content')
<div class="container py-4 pb-5">
    @include('components.navigation.breadcrumb', ['items' => [
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'Learn', 'url' => route('learn.home')],
        ['label' => 'Crash courses', 'url' => route('crashcourses.index')],
        ['label' => rtrim($crashcourse->title, '. ')],
    ]])
    <header class="learn-home__intro mb-5">
        <h1 class="mb-3">{{ rtrim($crashcourse->title, '. ') }}</h1>
        <p class="text-muted mb-0">{{ $crashcourse->description }}</p>
    </header>

    @component('components.layout.editorial')
        <div class="row g-4 align-items-start">
            <div class="col-md-6 order-2 order-md-1">
                @if($crashcourse->cover_path)
                    <img src="{{ $crashcourse->cover_image() }}" alt="" class="d-block w-100 h-auto rounded-sm mb-4" decoding="async">
                @else
                    <div class="resource-card__art overflow-hidden rounded-sm mb-4">@include('learn.components.artwork', ['kind' => 'courses'])</div>
                @endif
                <h2 class="h5 mb-3">A little learning, every day</h2>
                <ul class="list-unstyled text-muted mb-0">
                    @if($crashcourse->lessons_count)
                        <li class="mb-3 d-flex align-items-start">@icon('layers', ['mr' => 2, 'classes' => 'mt-1'])<span>{{ $crashcourse->lessons_count }} {{ Str::plural('lesson', $crashcourse->lessons_count) }} in this course</span></li>
                    @endif
                    <li class="mb-3 d-flex align-items-start">@icon('mail-open', ['mr' => 2, 'classes' => 'mt-1'])<span>One lesson delivered to your inbox each day</span></li>
                    <li class="d-flex align-items-start">@icon('bookmark', ['mr' => 2, 'classes' => 'mt-1'])<span>Keep the emails to revisit what you’ve learned</span></li>
                </ul>
            </div>
            <div class="col-md-6 order-1 order-md-2">
                @include('crashcourses.card', ['formId' => 'crashcourse-form', 'preview' => false])
            </div>
        </div>
        @slot('sidebar')
            @include('components.promotions.ads', ['ads' => ['ebook', 'escore'], 'variant' => 'cards'])
        @endslot
    @endcomponent
</div>
@endsection
