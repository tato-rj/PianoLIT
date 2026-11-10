@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" href="{{ mix('css/vendor/cropper/cropper.min.css') }}">
@endsection

@section('content')
@php
    $editing = $quiz !== null;
    $hasOldInput = session()->hasOldInput('quiz_editor') || session()->hasOldInput('questions') || session()->hasOldInput('title');
    $selectedTopics = $hasOldInput ? old('topics', []) : ($editing ? $quiz->topics->pluck('id')->all() : []);
    $selectedTopics = is_array($selectedTopics) ? $selectedTopics : [];
    $questionRows = $hasOldInput ? old('questions', []) : ($editing ? collect($quiz->questions)->map(fn ($question) => array_merge([$question['Q']], $question['A']))->all() : [['', '', '', '', '']]);
    $questionRows = is_array($questionRows) ? $questionRows : [];
    $correctAnswers = $hasOldInput ? old('correct_answers', []) : [];
    $correctAnswers = is_array($correctAnswers) ? $correctAnswers : [];
@endphp
<div class="content-wrapper"><div class="container-fluid">
    @component('admin.components.page.title', [
        'title' => $editing ? 'Edit '.$quiz->title : 'New quiz',
        'subtitle' => 'Manage quiz details, cover image and questions.',
        'breadcrumbs' => [['label' => 'Quizzes', 'url' => route('admin.quizzes.index', $returnFilters)]],
    ])
        @if($editing)
        <a href="{{ route('quizzes.show', $quiz) }}" target="_blank" rel="noopener noreferrer" class="btn btn-secondary btn-sm">@icon($quiz->published_at ? 'external-link' : 'eye'){{ $quiz->published_at ? 'View quiz' : 'Preview quiz' }}</a>
        @endif
        <a href="#quiz-details" class="btn btn-secondary btn-sm">Details</a>
        <a href="#quiz-questions" class="btn btn-secondary btn-sm">Questions</a>
    @endcomponent
    <form id="quiz-editor" method="POST" action="{{ $editing ? route('admin.quizzes.update', ['quiz' => $quiz] + $returnFilters) : route('admin.quizzes.store', $returnFilters) }}" enctype="multipart/form-data" data-quiz-editor>
        @csrf
        @if($editing) @method('PATCH') @endif
        <input type="hidden" name="quiz_editor" value="1">
        <div class="admin-editor-grid">
            <div class="admin-editor-stack">
                @include('admin.pages.quizzes.fields.details')
                @include('admin.pages.quizzes.question.layout')
            </div>
            <div class="admin-editor-stack">
                @component('admin.components.editor.section', ['id' => 'quiz-cover', 'title' => 'Cover image'])
                    @image(['name' => '', 'image' => $editing && $quiz->cover_path ? $quiz->cover_image() : asset('images/misc/placeholder-image-wide.png'), 'empty' => false, 'buttonLabel' => $editing && $quiz->cover_path ? 'Change image' : 'Choose image', 'alt' => $editing && $quiz->cover_path ? 'Cover image for '.$quiz->title : 'No cover image selected', 'errorKey' => 'cover_image', 'accept' => 'image/jpeg'])
                    <p class="small text-muted mb-0">JPG · up to 1 MB. Choose an image, adjust the crop, then confirm it.</p>
                @endcomponent
                @include('admin.pages.quizzes.fields.topics')
            </div>
        </div>
        @component('admin.components.table-save-actions', ['status' => $hasOldInput ? 'Unsaved changes' : ($editing ? 'All changes saved' : 'Not saved yet')])
            <button type="submit" id="submit-button" class="btn btn-primary" data-save-quiz>@icon($editing ? 'check' : 'plus'){{ $editing ? 'Save changes' : 'Create quiz' }}</button>
        @endcomponent
        @if($editing)<p class="small text-muted mt-3">Created by {{ optional($quiz->creator)->name ?: 'Unknown creator' }}.</p>@endif
    </form>
</div></div>
@endsection

@section('scripts')
<script src="{{ mix('js/vendor/cropper.min.js') }}"></script>
<script src="{{ mix('js/views/admin-quiz-editor.js') }}"></script>
@endsection
