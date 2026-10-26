@extends('layouts.app', [
    'title' => rtrim($pianist->name, '. ').' | PianoLIT Learn', 'bodyClass' => 'learn-home pianist-page',
    'shareable' => [
        'keywords' => $pianist->name.',piano,classical music,piano recordings',
        'title' => rtrim($pianist->name, '. ').' | Great Pianists',
        'description' => Str::limit(trim(preg_replace('/\s+/u', ' ', strip_tags((string) $pianist->biography))), 160),
        'thumbnail' => $pianist->cover_path ? storage($pianist->cover_path) : asset('images/misc/thumbnails/pianists.jpg'),
        'created_at' => $pianist->created_at ? $pianist->created_at->format(DateTime::ISO8601) : '',
        'updated_at' => $pianist->updated_at ? $pianist->updated_at->format(DateTime::ISO8601) : '',
    ],
])

@section('content')
@php
    $nationality = optional($pianist->country)->nationality ?: optional($pianist->country)->name;
    $biography = trim((string) $pianist->biography);
    $hasRecordings = preg_match('/\A[1-9]\d*\z/', trim((string) $pianist->itunes_id));
@endphp
<div class="container py-4 pb-5">
    @include('components.navigation.breadcrumb', ['items' => [
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'Learn', 'url' => route('learn.home')],
        ['label' => 'Great Pianists', 'url' => route('resources.pianists.index')],
        ['label' => rtrim($pianist->name, '. ')],
    ]])
    @component('components.layout.editorial')
        <article>
            <header class="row g-4 align-items-center mb-5">
                @if($pianist->cover_path)
                    <div class="col-4 col-md-3">
                        <img src="{{ storage($pianist->cover_path) }}" class="pianist-portrait d-block" alt="Portrait of {{ rtrim($pianist->name, '. ') }}" decoding="async">
                    </div>
                @endif
                <div class="{{ $pianist->cover_path ? 'col-8 col-md-9' : 'col-12' }}">
                    @if($nationality)<p class="small text-muted mb-2">{{ $nationality }}</p>@endif
                    <h1 class="h2 mb-3">{{ rtrim($pianist->name, '. ') }}</h1>
                    @if($pianist->born_at || $pianist->died_at)
                        <dl class="pianist-dates small text-muted mb-4">
                            @if($pianist->born_at)<div><dt>Born</dt><dd>{{ $pianist->born_at }}</dd></div>@endif
                            @if($pianist->died_at)<div><dt>Died</dt><dd>{{ $pianist->died_at }}</dd></div>@endif
                        </dl>
                    @endif
                    @if($hasRecordings)<a class="btn btn-primary" href="#recordings">@icon('headphones')Explore recordings</a>@endif
                </div>
            </header>
            @if($biography)
                <section class="pianist-biography mb-5" aria-labelledby="pianist-biography-title">
                    <h2 class="h5 mb-3" id="pianist-biography-title">Biography</h2>
                    @foreach(preg_split('/\R\s*\R/', $biography) as $paragraph)<p>{{ $paragraph }}</p>@endforeach
                </section>
            @endif
            <section id="recordings" class="border-top pt-4 mb-5" aria-labelledby="pianist-recordings-title">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                    <h2 class="h4 mb-0" id="pianist-recordings-title">Recordings</h2>
                    <img src="{{ asset('images/icons/apple-music.svg') }}" alt="Apple Music" class="pianist-apple-music" width="88" loading="lazy">
                </div>
                @if($hasRecordings)
                    <div id="api-results" data-pianist-recordings data-itunes="{{ trim($pianist->itunes_id) }}" aria-busy="true">
                        <p class="small text-muted mb-4" data-recordings-status role="status" aria-live="polite">Loading albums from Apple Music…</p>
                        <div class="row g-3" data-recordings-results></div>
                        <button type="button" class="btn btn-secondary" data-recordings-retry hidden>@icon('rotate-ccw')Try again</button>
                        <noscript><p class="text-muted mb-0">Enable JavaScript to browse this pianist’s recordings.</p></noscript>
                    </div>
                    <template id="pianist-album-template">@include('pianists.album')</template>
                @else
                    <p class="text-muted mb-0">Recordings aren’t available for this profile yet. Explore another pianist to keep discovering.</p>
                @endif
            </section>
        </article>
        @if($morePianists->isNotEmpty())
            <section class="border-top pt-4 mt-5" aria-labelledby="more-pianists-title">
                <h2 class="h4 mb-4" id="more-pianists-title">Meet more pianists</h2>
                <div class="row g-4">
                    @foreach($morePianists as $artist)<div class="col-sm-6 col-xl-4">@include('pianists.card', ['pianist' => $artist, 'cardId' => 'more-pianist-'.$artist->id])</div>@endforeach
                </div>
            </section>
        @endif
        <div class="mt-5">@include('components.newsletter.invitation', ['headingId' => 'pianist-newsletter-title', 'preview' => false])</div>
        @slot('sidebar')
            @include('components.promotions.ads', ['ads' => ['crashcourse', 'ebook', 'escore'], 'variant' => 'cards'])
        @endslot
    @endcomponent
</div>
@endsection

@push('scripts')
<script src="{{ mix('js/views/pianists.js') }}" defer></script>
@endpush
