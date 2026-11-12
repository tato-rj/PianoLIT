@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" href="{{ mix('css/vendor/cropper/cropper.min.css') }}">
@endsection

@section('content')
<div class="content-wrapper"><div class="container-fluid">
    @component('admin.components.page.title', [
        'title' => 'Edit course',
        'subtitle' => $crashcourse->title,
        'breadcrumbLabel' => $crashcourse->title,
        'breadcrumbs' => [['label' => 'Crash courses', 'url' => route('admin.crashcourses.index', $returnFilters)]],
    ])
        <a href="{{ route('crashcourses.show', $crashcourse) }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-secondary">@icon('globe')View course</a>
    @endcomponent

    @include('admin.pages.crashcourses.edit.form')

    @php($lessons = $crashcourse->lessons)
    @component('admin.components.table', [
        'id' => 'course-lessons',
        'label' => 'Course lessons',
        'items' => $lessons,
        'columns' => ['Order', 'Subject', 'Reading time', ['label' => 'Actions', 'hidden' => true]],
        'emptyTitle' => 'No lessons yet',
    ])
        @slot('toolbar')
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                <h2 class="m-0">Lessons</h2>
                <a href="{{ route('admin.crashcourses.lessons.create', $crashcourse) }}" class="btn btn-sm btn-dark">@icon('plus')New lesson</a>
            </div>
        @endslot
        @foreach($lessons as $lesson)
            @include('admin.pages.crashcourses.edit.lesson-row')
        @endforeach
    @endcomponent

    @if($lessons->isNotEmpty())
        @component('admin.components.editor.section', [
            'id' => 'feedback-email', 'title' => 'Feedback email', 'class' => 'mb-4',
            'description' => 'Sent automatically after the final lesson.',
        ])
            @slot('actions')
                @include('admin.components.table-action', ['tableAction' => [
                    'label' => 'Preview', 'icon' => 'eye', 'attributes' => [
                        'data-bs-toggle' => 'modal', 'data-bs-target' => '#feedback-preview-modal', 'aria-label' => 'Preview feedback email',
                    ],
                ]])
            @endslot
        @endcomponent
        @include('admin.pages.crashcourses.edit.preview-modal', ['previewId' => 'feedback-preview-modal', 'previewTitle' => 'Preview feedback email',
            'sendUrl' => route('admin.crashcourses.feedback.send-to', $crashcourse),
            'previewUrl' => route('admin.crashcourses.feedback.preview', $crashcourse),
        ])
    @endif
</div></div>

@foreach($lessons as $lesson)
    @include('admin.pages.crashcourses.edit.preview-modal', ['previewId' => 'lesson-'.$lesson->id.'-preview-modal', 'previewTitle' => 'Preview lesson',
        'subject' => $lesson->subject,
        'sendUrl' => route('admin.crashcourses.lessons.send-to', compact('crashcourse', 'lesson')),
        'previewUrl' => route('admin.crashcourses.lessons.preview', compact('crashcourse', 'lesson')),
    ])
@endforeach
@include('admin.components.modals.delete', ['deleteDescription' => 'Delete this lesson?'])
<template id="course-order-success">
    @include('components.feedback.alert', ['color' => 'green', 'message' => 'Lesson order saved.', 'floating' => 'top', 'dismissible' => true])
</template>
<template id="course-order-error">
    @include('components.feedback.alert', ['color' => 'red', 'message' => 'Could not save the lesson order. Reload the page and try again.', 'floating' => 'top', 'dismissible' => true])
</template>
@endsection

@section('scripts')
<script src="{{ mix('js/vendor/cropper.min.js') }}"></script>
<script src="{{ mix('js/views/admin-crashcourse-editor.js') }}" data-course-reorder-url="{{ route('admin.crashcourses.lessons.reorder', $crashcourse) }}"></script>
@endsection
