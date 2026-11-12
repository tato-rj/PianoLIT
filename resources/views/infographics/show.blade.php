@php
    $name = rtrim($infograph->name, '. ');
    $description = \App\Services\Learn\InfographicGuide::description($infograph);
    $guide = \App\Services\Learn\InfographicGuide::forArtwork($infograph);
    $alt = $guide['alt'] ?? 'Preview of '.$name;
    $image = $infograph->cover_path ? storage($infograph->thumbnail_path) : null;
    $searchDescription = Str::limit($guide['text'] ?? $description, 157, '…');
    $breadcrumbs = [
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'Learn', 'url' => route('learn.home')],
        ['label' => 'Infographics', 'url' => route('resources.infographs.index')],
        ['label' => $name],
    ];
    if ($image) {
        $searchEntity = [
            '@type' => 'ImageObject',
            'contentUrl' => $image,
            'name' => $name,
            'caption' => $alt,
            'description' => $guide['text'] ?? $description,
            'mainEntityOfPage' => $canonical,
        ];
    }
@endphp
@extends('layouts.app', ['title' => $name.' | PianoLIT Learn', 'bodyClass' => 'learn-home'])

@section('page-metadata')
    @include('components.seo.resource-head', ['searchTitle' => $name, 'searchImage' => $image])
@endsection

@section('content')
<div class="container py-4 pb-5">
    @include('components.navigation.breadcrumb', ['items' => $breadcrumbs])

    @component('components.layout.editorial')
        <div class="row g-4 g-xl-5 align-items-start mb-5">
            <div class="col-md-5 order-2 order-md-1">
                @if($image)
                    <img src="{{ $image }}" alt="{{ $alt }}" @if($guide) aria-describedby="infographic-summary" @endif class="d-block w-100 border rounded-sm bg-white" decoding="async">
                @endif
            </div>
            <div class="col-md-7 order-1 order-md-2">
                @if($infograph->topics->isNotEmpty())
                    <div class="d-flex flex-wrap gap-2 mb-3" aria-label="Infographic topics">
                        @foreach($infograph->topics as $topic)
                            <a href="{{ route('resources.infographs.index', ['topics' => $topic->slug]) }}" class="btn btn-secondary btn-sm">{{ ucfirst($topic->name) }}</a>
                        @endforeach
                    </div>
                @endif
                <header class="mb-4">
                    <h1 class="h3 mb-3">{{ $name }}</h1>
                    @if($description)<p class="text-muted mb-0">{{ $description }}</p>@endif
                </header>
                <div class="mb-4">@include('infographics.download')</div>
                <dl class="small border-top pt-4 mb-4">
                    <dt class="mb-1">Your download</dt>
                    <dd class="text-muted mb-3">
                        {{ strtoupper(extension($infograph->cover_path) ?: 'JPG') }}
                        @if($infograph->width && $infograph->height)
                            · {{ number_format($infograph->width) }} × {{ number_format($infograph->height) }} pixels
                        @endif
                    </dd>
                    <dt class="mb-1">PianoLIT license</dt>
                    <dd class="text-muted mb-0">Free for personal and commercial use with attribution.</dd>
                </dl>
                @if($infograph->downloads > 4)
                    <p class="small text-muted">@icon('file-down', ['mr' => 1]){{ number_format($infograph->downloads) }} downloads</p>
                @endif
                <div class="border-top pt-4" data-infographic-feedback>
                    <h2 class="h6 mb-2">Was this guide helpful?</h2>
                    <div class="d-flex gap-2 mb-2">
                        <button type="button" class="btn btn-secondary btn-sm review" data-value="1" data-url="{{ route('infographs.update-score', $infograph) }}" aria-pressed="false">@icon('thumbs-up')Yes</button>
                        <button type="button" class="btn btn-secondary btn-sm review" data-value="0" data-url="{{ route('infographs.update-score', $infograph) }}" aria-pressed="false">@icon('thumbs-down')No</button>
                    </div>
                    <p class="small text-muted mb-0 infograph-feedback" role="status" aria-live="polite">Your feedback helps us improve these guides.</p>
                </div>
            </div>
        </div>
        @if($guide)
            <section class="border-top pt-4 mb-5" aria-labelledby="infographic-summary-title">
                <h2 id="infographic-summary-title" class="h4 mb-3">Guide in text</h2>
                <p id="infographic-summary">{{ $guide['text'] }}</p>
                @isset($guide['correction'])
                    <p>{{ $guide['correction'] }} <a href="{{ $guide['source'] }}">{{ $guide['source_label'] }}</a></p>
                @endisset
                @isset($guide['tool'])
                    <p><a href="{{ route($guide['tool']['route']) }}">{{ $guide['tool']['label'] }}</a></p>
                @endisset
            </section>
        @endif
        @if($related->isNotEmpty())
            <section class="border-top pt-4" aria-labelledby="related-infographics-title">
                <h2 id="related-infographics-title" class="h4 mb-4">Related infographics</h2>
                <div class="row g-4">
                    @foreach($related as $item)
                        <div class="col-md-6">@include('infographics.card')</div>
                    @endforeach
                </div>
            </section>
        @endif
        @slot('sidebar')
            @include('components.promotions.ads', ['ads' => ['crashcourse', 'ebook', 'escore'], 'variant' => 'cards'])
        @endslot
    @endcomponent
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const $feedback = $('[data-infographic-feedback]');
    const $buttons = $feedback.find('.review');
    $buttons.on('click', function () {
        const $button = $(this);
        if ($feedback.attr('aria-busy') === 'true' || $button.prop('disabled')) return;
        $feedback.attr('aria-busy', 'true');
        $buttons.prop('disabled', true);
        $.post($button.attr('data-url'), {liked: $button.attr('data-value')})
            .done(function () {
                $button.removeClass('btn-secondary').addClass('btn-primary').attr('aria-pressed', 'true');
                $feedback.find('.infograph-feedback').text('Thanks for your feedback!');
            })
            .fail(function (response) {
                $buttons.prop('disabled', false);
                $feedback.find('.infograph-feedback').text(response.status === 429
                    ? 'Please wait before sending more feedback.'
                    : 'Your feedback could not be saved. Please try again.');
            })
            .always(function () { $feedback.attr('aria-busy', 'false'); });
    });
});
</script>
@endpush
