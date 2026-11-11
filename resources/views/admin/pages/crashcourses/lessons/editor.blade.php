@extends('admin.layouts.app')
@section('head')
<script src="{{ mix('js/vendor/tinymce/tinymce.min.js') }}"></script>
@endsection
@section('content')
@php($editing = $lesson && $lesson->exists)
<div class="content-wrapper"><div class="container-fluid">
    @component('admin.components.page.title', ['title' => $editing ? 'Edit lesson' : 'New lesson', 'subtitle' => $crashcourse->title, 'breadcrumbs' => [['label' => 'Crash courses', 'url' => route('admin.crashcourses.index')], ['label' => $crashcourse->title, 'url' => route('admin.crashcourses.edit', $crashcourse)]]])
        @if($editing)<a class="btn btn-sm btn-secondary" href="{{ route('admin.crashcourses.lessons.preview', compact('crashcourse', 'lesson')) }}" target="_blank" rel="noopener noreferrer">@icon('external-link')Saved email</a>@endif
    @endcomponent
    <form method="POST" action="{{ $editing ? route('admin.crashcourses.lessons.update', compact('crashcourse', 'lesson')) : route('admin.crashcourses.lessons.store', $crashcourse) }}" data-lesson-editor autocomplete="off">
        @csrf
        @if($editing) @method('PATCH') @endif
        <input type="hidden" name="email_revision" value="{{ old('email_revision', $emailRevision) }}">
        @component('admin.components.editor.section', ['id' => 'lesson-details', 'title' => 'Email details', 'class' => 'mb-4'])
            <div class="row">
                <div class="col-lg-7">@input(['bag' => 'default', 'id' => 'lesson-subject', 'name' => 'subject', 'label' => 'Subject', 'value' => session()->hasOldInput() ? '' : optional($lesson)->subject, 'limit' => 120])</div>
                <div class="col-lg-5">@input(['bag' => 'default', 'id' => 'lesson-preheader', 'name' => 'preview_text', 'label' => 'Preview text', 'required' => false, 'limit' => 180, 'placeholder' => 'Shown beside the subject in the inbox'])</div>
            </div>
            <p class="small text-muted mb-0">Use <code>[first_name]</code> to include the reader’s name.</p>
        @endcomponent
        @include('admin.components.email-builder.editor')
        @component('admin.components.table-save-actions', ['status' => $editing ? 'Changes take effect when you save.' : 'Not saved yet'])
            <button type="submit" class="btn btn-primary" data-save-lesson disabled>@icon('check'){{ $editing ? 'Save lesson' : 'Create lesson' }}</button>
        @endcomponent
        <noscript><p>Enable JavaScript to edit this email.</p></noscript>
    </form>
</div></div>
@endsection
@section('scripts')
<script src="{{ mix('js/views/admin-email-builder.js') }}"></script>
@endsection
