@extends('admin.layouts.app')

@section('head')
    <script src="https://cloud.tinymce.com/5/tinymce.min.js?apiKey=80i9j60sixlsp84wyu3rquuelix1zbkhrodmrne6znnns8j1"></script>
    <script src="{{ mix('js/tinyeditor/tiny.js') }}"></script>
@endsection

@section('content')
@php
    $media = app(\App\Services\Shop\DigitalMedia::class);
    $mediaState = $media->state($product);
    $displayImages = collect($mediaState['images'])->reject(fn ($image) => $image['removed'])->values();
    $removedImages = collect($mediaState['images'])->filter(fn ($image) => $image['removed'])->values();
    $previewPages = $media->previews($product, $mediaState);
    $isBook = $kind === 'ebooks';
    $selectedTopics = old('topics', old('title') !== null ? [] : $product->topics->pluck('id')->all());
@endphp
<div class="content-wrapper"><div class="container-fluid">
    @component('admin.components.page.title', [
        'title' => 'Edit', 'subtitle' => $product->title,
        'breadcrumbs' => [['label' => $isBook ? 'eBooks' : 'eScores', 'url' => route('admin.'.$kind.'.index')]],
    ])@endcomponent
    <form method="POST" action="{{ route('admin.'.$kind.'.update', $product) }}" enctype="multipart/form-data" id="digital-details">
        @csrf @method('PATCH')
        <input type="hidden" name="media_revision" value="{{ $mediaState['revision'] }}">
        @component('admin.components.editor.section', ['id' => 'digital-details', 'sectionId' => 'product-details', 'title' => 'Product details', 'class' => 'mb-4'])
            <div class="row g-3">
                <div class="col-md-6">@input(['id' => 'digital-title', 'name' => 'title', 'label' => 'Title', 'value' => $product->title, 'limit' => 120])</div>
                <div class="col-md-6">@input(['id' => 'digital-author', 'name' => 'author', 'label' => $isBook ? 'Author(s)' : 'Composer(s)', 'value' => $product->author, 'limit' => 120, 'required' => false])</div>
                <div class="col-12">@textarea(['bag' => 'default', 'id' => 'digital-subtitle', 'name' => 'subtitle', 'label' => 'Short description', 'value' => $product->subtitle, 'limit' => 238, 'rows' => 2])</div>
                <div class="col-sm-4">@input(['id' => 'digital-pages', 'name' => 'pages_count', 'label' => 'Number of pages', 'value' => $product->pages_count, 'type' => 'number', 'min' => 1, 'limit' => 10000])</div>
                <div class="col-sm-4">@input(['id' => 'digital-price', 'name' => 'price', 'label' => 'Price in USD', 'value' => $product->price, 'type' => 'number', 'limit' => 10000, 'step' => '0.01'])</div>
                <div class="col-sm-4">@input(['id' => 'digital-discount', 'name' => 'discount', 'label' => 'Discount (%)', 'value' => $product->discount, 'type' => 'number', 'limit' => 100, 'step' => 1, 'required' => false])</div>
                <div class="col-12">
                    <fieldset class="mb-3"><legend class="h6">Topics</legend><div class="d-flex flex-wrap gap-3">
                    @forelse($topics as $topic)
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="topics[]" value="{{ $topic->id }}" id="digital-topic-{{ $topic->id }}" @if(in_array($topic->id, is_array($selectedTopics) ? $selectedTopics : [])) checked @endif>
                            <label class="form-check-label" for="digital-topic-{{ $topic->id }}">{{ $topic->name }}</label>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No topics available.</p>
                    @endforelse
                    </div></fieldset>
                </div>
                <div class="col-12">
                    <label for="editor" class="h6">Description</label>
                    @tinyeditor(['bag' => 'default', 'name' => 'description', 'rows' => 8, 'value' => old('description', $product->description)])
                </div>
            </div>
            <div class="border-top mt-4 pt-3">
                <h3 class="h6">Product files</h3>
                <p class="small text-muted">Keep a field empty to retain its current file. Uploading a PDF also generates the Look inside preview from its first seven pages.</p>
                <div class="row g-3">
                    <div class="col-md-6">@file(['bag' => 'default', 'name' => 'pdf_file', 'value' => $product->pdf_path, 'label' => 'PDF', 'required' => false])</div>
                    @if($isBook)
                    <div class="col-md-6">@file(['bag' => 'default', 'name' => 'epub_file', 'value' => $product->epub_path, 'label' => 'EPUB', 'required' => false])</div>
                    @else
                    <div class="col-md-6">@file(['bag' => 'default', 'name' => 'audio_file', 'value' => $product->audio_path, 'label' => 'Audio sample', 'required' => false])</div>
                    @endif
                </div>
                <p class="small text-muted">Maximum 50 MB per file</p>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">@icon('check')Save details</button>
        @endcomponent
    </form>
    <div class="row g-4 mb-4 align-items-start">
        <div class="col-lg-8">
            @include('admin.pages.digital.gallery')
        </div>
        <div class="col-lg-4">
            @include('admin.components.shop.closeup', ['closeupProduct' => $product, 'closeupKind' => $kind])
        </div>
    </div>
    @component('admin.components.editor.section', ['id' => 'digital-preview', 'sectionId' => 'look-inside', 'title' => 'Look inside', 'description' => 'The PDF cover and the next six pages. Shorter PDFs show all available pages.', 'class' => 'mb-4'])
        @if($product->pdf_path)
            @slot('actions')
                <form method="POST" action="{{ route('admin.'.$kind.'.pdf-preview', $product) }}">
                    @csrf
                    <input type="hidden" name="media_revision" value="{{ $mediaState['revision'] }}">
                    <button type="submit" class="btn btn-secondary btn-sm">@icon('refresh-cw'){{ $previewPages ? 'Regenerate preview' : 'Generate preview' }}</button>
                </form>
            @endslot
        @endif
        @if($previewPages)
            <div class="row g-3">
                @foreach($previewPages as $page)
                <div class="col-6 col-sm-3 col-lg-2">
                    <a href="{{ asset('storage/'.$page) }}" target="_blank" rel="noopener noreferrer" aria-label="Open preview page {{ $loop->iteration }}">
                        <img src="{{ asset('storage/'.$page) }}" alt="{{ $product->title }} preview page {{ $loop->iteration }}" class="img-fluid border rounded" loading="lazy">
                    </a>
                    <p class="small text-muted mt-2 mb-0">{{ $loop->first ? 'Cover' : 'Page '.$loop->iteration }}</p>
                </div>
                @endforeach
            </div>
        @else
            <p class="text-muted mb-0">{{ $product->pdf_path ? 'No PDF preview generated. Generate a preview from the saved PDF.' : 'No PDF uploaded. Save a PDF to generate the preview.' }}</p>
        @endif
    @endcomponent
    @include('admin.pages.reviews.fake', ['product' => $product])
</div></div>
@endsection
