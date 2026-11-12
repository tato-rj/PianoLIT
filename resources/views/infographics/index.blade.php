@php
    $searchTitle = 'Music Infographics'.($infographs->currentPage() > 1 ? ' | Page '.$infographs->currentPage() : '');
    $searchDescription = 'Free visual guides to music theory, piano practice, and music history. Browse diagrams of chords, rhythm, key signatures, and more.';
    $breadcrumbs = [
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'Learn', 'url' => route('learn.home')],
        ['label' => 'Infographics'],
    ];
@endphp
@extends('layouts.app', ['title' => $searchTitle.' | PianoLIT Learn', 'bodyClass' => 'learn-home'])

@section('page-metadata')
    @include('components.seo.resource-head', ['searchImage' => asset('images/misc/thumbnails/infographs.jpg')])
@endsection

@section('content')
@php
    $selectedTopics = array_values(array_filter((array) (is_array(request('topics')) ? request('topics') : explode(' ', (string) request('topics', ''))), function ($topic) { return is_string($topic) && trim($topic) !== ''; }));
    $topicLinks = [['label' => 'All infographics', 'url' => route('resources.infographs.index'), 'active' => empty($selectedTopics), 'variant' => 'secondary']];
    foreach ($topics as $topic) {
        $topicLinks[] = ['label' => ucfirst($topic->name), 'url' => route('resources.infographs.index', ['topics' => $topic->slug]), 'active' => in_array($topic->slug, $selectedTopics, true)];
    }
@endphp
<div class="container py-4 pb-5">
    @include('components.navigation.breadcrumb', ['items' => $breadcrumbs])
    @component('components.layout.hub-header', ['heading' => 'Infographics', 'preview' => false])
        Free visual guides to music theory and piano practice.
    @endcomponent

    @include('components.navigation.topic-navigation', ['items' => $topicLinks, 'label' => 'Infographic topics'])

    @component('components.layout.editorial')
        @if($infographs->isNotEmpty())
            <section aria-labelledby="infographics-directory-title">
                <div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2 mb-4">
                    {{-- <h2 id="infographics-directory-title" class="h5 mb-0">Visual guides</h2> --}}
                    <p class="small text-muted mb-0">Showing {{ $infographs->firstItem() }}–{{ $infographs->lastItem() }} of {{ $infographs->total() }} {{ Str::plural('infographic', $infographs->total()) }}</p>
                </div>
                <div class="row gx-4 gy-5">
                    @foreach($infographs as $item)
                        <div class="col-md-6">@include('infographics.card')</div>
                    @endforeach
                </div>
                @if($infographs->hasPages())
                    <div class="mt-5">{{ $infographs->onEachSide(1)->links() }}</div>
                @endif
            </section>
        @else
            <section aria-labelledby="infographics-empty-title" class="py-4">
                <h2 id="infographics-empty-title" class="h5">{{ $selectedTopics ? 'No infographics match these topics' : 'No infographics available' }}</h2>
                <p class="text-muted">{{ $selectedTopics ? 'Try another topic or browse all of our visual guides.' : 'Browse the other tools and resources in Learn.' }}</p>
                <a class="btn btn-secondary" href="{{ $selectedTopics ? route('resources.infographs.index') : route('learn.home') }}">{{ $selectedTopics ? 'View all infographics' : 'Explore Learn' }} @icon('arrow-right', ['mr' => 0, 'ml' => 2])</a>
            </section>
        @endif
        @slot('sidebar')
            @include('components.promotions.ads', ['ads' => ['crashcourse', 'ebook', 'escore'], 'variant' => 'cards'])
        @endslot
    @endcomponent
</div>
@endsection
