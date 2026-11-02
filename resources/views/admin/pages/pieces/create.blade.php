@extends('admin.layouts.app')

@section('content')
@php
    $hasOldInput = session()->hasOldInput();
    $isPublic = (bool) old('is_public', !$hasOldInput);
    $selectedTags = old('tags', []);
    $selectedTags = is_array($selectedTags) ? $selectedTags : [];
@endphp
<div class="content-wrapper"><div class="container-fluid">
    @include('admin.components.page.title', [
        'title' => 'New piece',
        'subtitle' => 'Add piece details, classification and media.',
        'breadcrumbs' => [['label' => 'Pieces', 'url' => route('admin.pieces.index')]],
    ])

    @component('admin.components.editor.section', ['id' => 'piece-references', 'title' => 'Level references', 'class' => 'mb-4'])
        <div class="admin-editor-options">
            @include('admin.pages.pieces.fields.rankings')
        </div>
    @endcomponent

    <form id="create-piece" method="POST" action="{{ route('admin.pieces.store') }}" autocomplete="off" enctype="multipart/form-data"
        data-piece-editor data-piece-create data-description-url="{{ route('admin.pieces.description-auto-complete') }}"
        data-validate-name-url="{{ route('admin.pieces.validate-name') }}" data-single-lookup-url="{{ route('admin.pieces.single-lookup') }}" data-multi-lookup-url="{{ route('admin.pieces.multi-lookup') }}">
        @csrf
        <fieldset>
            <legend class="visually-hidden">New piece</legend>
            <div class="admin-editor-grid">
                <div class="admin-editor-stack">
                    @include('admin.pages.pieces.fields.details')
                    @include('admin.pages.pieces.fields.classification')
                    @include('admin.pages.pieces.fields.description')
                    @manager
                        @include('admin.pages.pieces.fields.score')
                        @include('admin.pages.pieces.fields.videos')
                    @endmanager
                </div>
                <div class="admin-editor-stack">
                    @component('admin.components.editor.section', ['id' => 'piece-existing', 'title' => 'Existing pieces'])
                        <div id="validation-results" aria-live="polite" aria-busy="false">
                            <p class="small text-muted mb-0" data-duplicate-status>Enter a piece name to check for similar pieces.</p>
                            <div class="results" data-duplicate-results></div>
                            <div data-duplicate-error hidden>
                                @alert(['color' => 'red', 'message' => 'Existing pieces could not be checked. Edit the name to try again.'])
                            </div>
                        </div>
                    @endcomponent
                    @include('admin.pages.pieces.fields.tags', ['warnOnManyTags' => true])
                </div>
            </div>
        </fieldset>
        <div class="admin-editor-save">
            <span class="small text-muted" role="status" aria-live="polite" data-editor-save-status>Not saved yet</span>
            <button type="submit" id="submit-button" class="btn btn-primary" data-save-piece>@icon('plus')Add piece</button>
        </div>
    </form>
</div></div>
@include('admin.pages.pieces.rankings', ['ranking' => 'abrsm'])
@include('admin.pages.pieces.rankings', ['ranking' => 'rcm'])
@endsection

@section('scripts')
<script src="{{ asset('js/vendor/lookup.js') }}"></script>
<script src="{{ mix('js/views/admin-piece-editor.js') }}"></script>
<script src="{{ mix('js/views/admin-piece-create.js') }}"></script>
@endsection
