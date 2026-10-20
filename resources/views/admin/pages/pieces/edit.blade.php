@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.4.3/cropper.min.css">
@endsection

@section('content')
@php
    $hasOldInput = session()->hasOldInput();
    $isPublic = (bool) old('is_public', $hasOldInput ? false : $piece->is_public_domain);
    $selectedTags = old('tags', $hasOldInput ? [] : $piece->tags->pluck('id')->all());
    $selectedTags = is_array($selectedTags) ? $selectedTags : [];
@endphp
<div class="content-wrapper"><div class="container-fluid">
    @include('admin.components.page.title', [
        'title' => 'Edit '.$piece->name,
        'subtitle' => 'Manage piece details, artwork and media.',
        'breadcrumbs' => [['label' => 'Pieces', 'url' => route('admin.pieces.index')]],
    ])

    @include('admin.pages.pieces.edit.options')

    <form id="edit-form" method="POST" action="{{ route('admin.pieces.update', $piece) }}" enctype="multipart/form-data" data-piece-editor data-description-url="{{ route('admin.pieces.description-auto-complete') }}" data-piece-id="{{ $piece->id }}">
        @csrf
        @method('PATCH')
        <fieldset @cannot('update', $piece) disabled @endcannot>
            <legend class="visually-hidden">Piece editor</legend>
            <div class="admin-editor-grid">
                <div class="admin-editor-stack">
                    @include('admin.pages.pieces.edit.details')
                    @include('admin.pages.pieces.edit.classification')
                    @component('admin.components.editor.section', ['id' => 'piece-description', 'title' => 'Description'])
                        @slot('actions')
                            <button type="button" class="btn btn-sm btn-dark" data-sample-description>@icon('wand-sparkles')Use a sample</button>
                        @endslot
                        @textarea(['bag' => 'default', 'id' => 'piece-description-input', 'name' => 'description', 'label' => 'About this piece', 'value' => $piece->description, 'rows' => 5, 'required' => false, 'placeholder' => 'Enter a description'])
                        <p class="small text-danger mb-0" role="status" data-description-error hidden></p>
                    @endcomponent
                    @manager
                        @include('admin.pages.pieces.edit.score')
                        @include('admin.pages.pieces.edit.videos')
                    @endmanager
                </div>
                <div class="admin-editor-stack">
                    @component('admin.components.editor.section', ['id' => 'piece-artwork', 'title' => 'Cover image'])
                        @image(['name' => '', 'image' => $piece->cover_image() ?? asset('images/misc/placeholder-image-wide.png'), 'empty' => false, 'alt' => 'Cover image for '.$piece->name])
                    @endcomponent
                    @include('admin.pages.pieces.edit.tags')
                </div>
            </div>
        </fieldset>
        @can('update', $piece)
        <div class="admin-editor-save">
            <span class="small text-muted" role="status" aria-live="polite" data-editor-save-status>All changes saved</span>
            <button type="submit" class="btn btn-primary" data-save-piece>@icon('check')Save changes</button>
        </div>
        @else
        <p class="small text-muted">You can view this piece. Editing is available to its creator and managers.</p>
        @endcan
    </form>
    @manager
    @can('update', $piece)
    @if($piece->score_path)
    <form id="delete-score-form" method="POST" action="{{ route('admin.pieces.destroy-file', $piece) }}">
        @csrf
        @method('DELETE')
        <input type="hidden" name="method" value="deleteScore">
    </form>
    @endif
    @endcan
    @endmanager
    @include('admin.components.creator', ['model' => $piece, 'type' => 'piece'])
</div></div>
@include('admin.pages.pieces.rankings', ['ranking' => 'abrsm'])
@include('admin.pages.pieces.rankings', ['ranking' => 'rcm'])
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.4.3/cropper.min.js"></script>
<script src="{{ mix('js/views/admin-piece-editor.js') }}"></script>
@endsection
