@extends('layouts.app', [
    'title' => 'Infographics | PianoLIT Learn',
    'bodyClass' => 'learn-home',
    'shareable' => [
        'keywords' => 'infographics,music theory,piano,visual guides',
        'title' => 'Infographics | PianoLIT Learn',
        'description' => 'Explore music theory through free visual guides to download and keep.',
        'thumbnail' => asset('images/misc/thumbnails/infographs.jpg'),
        'created_at' => carbon('28-08-2019'),
        'updated_at' => carbon('28-08-2019'),
    ]])

@section('content')
@php
    $selectedTopics = array_values(array_filter((array) (is_array(request('topics')) ? request('topics') : explode(' ', (string) request('topics', ''))), function ($topic) { return is_string($topic) && trim($topic) !== ''; }));
    $topicLinks = [['label' => 'All infographics', 'url' => route('resources.infographs.index'), 'active' => empty($selectedTopics)]];
    foreach ($topics as $topic) {
        $topicLinks[] = ['label' => ucfirst($topic->name), 'url' => route('resources.infographs.index', ['topics' => $topic->slug]), 'active' => in_array($topic->slug, $selectedTopics, true)];
    }
@endphp
<div class="container py-4 pb-5">
    @include('components.breadcrumb', ['items' => [
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'Learn', 'url' => route('learn.home')],
        ['label' => 'Infographics'],
    ]])
    @component('components.hub-header', ['heading' => 'Infographics', 'preview' => false])
        A fresh way to see music theory.<br class="d-none d-md-block"> Free visual guides to explore, download, and keep close to your piano.
    @endcomponent

    @include('components.topic-navigation', ['items' => $topicLinks, 'label' => 'Infographic topics'])

    @component('components.editorial-layout')
        @if($infographs->isNotEmpty())
            <section aria-labelledby="infographics-directory-title">
                <div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2 mb-4">
                    <h2 id="infographics-directory-title" class="h5 mb-0">Visual guides</h2>
                    <p class="small text-muted mb-0">Showing {{ $infographs->firstItem() }}–{{ $infographs->lastItem() }} of {{ $infographs->total() }} {{ Str::plural('infographic', $infographs->total()) }}</p>
                </div>
                <div class="row g-4">
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
                <h2 id="infographics-empty-title" class="h5">{{ $selectedTopics ? 'No infographics match these topics' : 'New visual guides are on their way' }}</h2>
                <p class="text-muted">{{ $selectedTopics ? 'Try another topic or browse all of our visual guides.' : 'Explore our other learning tools while you wait.' }}</p>
                <a class="btn btn-secondary" href="{{ $selectedTopics ? route('resources.infographs.index') : route('learn.home') }}">{{ $selectedTopics ? 'View all infographics' : 'Explore Learn' }} @icon('arrow-right', ['mr' => 0, 'ml' => 2])</a>
            </section>
        @endif
        @slot('sidebar')
            @include('components.display.ads', ['ads' => ['crashcourse', 'ebook', 'escore'], 'variant' => 'cards'])
        @endslot
    @endcomponent
</div>
@endsection
