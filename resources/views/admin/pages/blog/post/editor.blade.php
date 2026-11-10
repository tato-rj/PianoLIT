@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" href="{{ mix('css/vendor/cropper/cropper.min.css') }}">
<script src="https://cloud.tinymce.com/5/tinymce.min.js?apiKey=80i9j60sixlsp84wyu3rquuelix1zbkhrodmrne6znnns8j1"></script>
<script src="{{ mix('js/tinyeditor/tiny.js') }}"></script>
@endsection

@section('content')
@php
    $editing = $post && $post->exists;
    $hasOldInput = session()->hasOldInput();
    $selectedTopics = old('topics', $hasOldInput || !$editing ? [] : $post->topics->pluck('id')->all());
    $selectedTopics = is_array($selectedTopics) ? $selectedTopics : [];
    $references = old('references', $hasOldInput || !$editing ? [] : $post->referencesArray);
    $references = is_array($references) ? $references : [];
    if (!$references) $references = [''];
@endphp
<div class="content-wrapper"><div class="container-fluid">
    @component('admin.components.page.title', [
        'title' => $editing ? 'Edit post' : 'New post',
        'subtitle' => $editing ? $post->title : 'Add post details, a cover image and article content.',
        'breadcrumbs' => [['label' => 'Posts', 'url' => route('admin.posts.index', $returnFilters)]],
    ])
        @if($editing)
        <a href="{{ route('posts.show', $post) }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-secondary">@icon($post->published_at ? 'globe' : 'eye'){{ $post->published_at ? 'View post' : 'Preview' }}</a>
        @endif
    @endcomponent

    <form id="post-editor" method="POST" action="{{ $editing ? route('admin.posts.update', ['post' => $post] + $returnFilters) : route('admin.posts.store', $returnFilters) }}" autocomplete="off" enctype="multipart/form-data" data-post-editor>
        @csrf
        @if($editing) @method('PATCH') @endif
        <div class="admin-editor-grid">
            <div class="admin-editor-stack">
                @component('admin.components.editor.section', ['id' => 'post-details', 'title' => 'Post details'])
                    @input(['bag' => 'default', 'id' => 'post-title', 'name' => 'title', 'label' => 'Title', 'placeholder' => 'Post title', 'value' => $hasOldInput ? '' : optional($post)->title, 'limit' => 120])
                    @textarea(['bag' => 'default', 'id' => 'post-description', 'name' => 'description', 'label' => 'Description', 'placeholder' => 'A short description of the post', 'value' => $hasOldInput ? '' : optional($post)->description, 'rows' => 4, 'limit' => 238])
                @endcomponent
                @component('admin.components.editor.section', ['id' => 'post-topics', 'title' => 'Topics'])
                    <div class="d-flex flex-wrap gap-3">
                        @forelse($topics as $topic)
                            @include('components.forms.checkbox', ['id' => 'post-topic-'.$topic->id, 'name' => 'topics[]', 'value' => $topic->id, 'label' => $topic->name, 'checked' => in_array($topic->id, $selectedTopics), 'classes' => 'mb-0'])
                        @empty
                            <p class="small text-muted mb-0">No topics yet. Manage topics from the Posts page.</p>
                        @endforelse
                    </div>
                    @include('components.forms.error', ['field' => 'topics', 'errorClasses' => 'd-block'])
                    @include('components.forms.error', ['field' => 'topics.*', 'errorClasses' => 'd-block'])
                @endcomponent
            </div>
            <div class="admin-editor-stack">
                @component('admin.components.editor.section', ['id' => 'post-cover', 'title' => 'Cover image'])
                    @image(['name' => $editing ? '' : 'cover_image', 'errorKey' => 'cover_image', 'accept' => '.jpg,.jpeg', 'buttonLabel' => $editing && $post->cover_path ? 'Change image' : 'Choose image', 'image' => $editing && $post->cover_path ? $post->cover_image() : asset('images/misc/placeholder-image-wide.png'), 'empty' => false, 'alt' => $editing ? 'Cover image for '.$post->title : 'No cover image selected'])
                    <p class="small text-muted mb-3">JPG · up to 1 MB. Confirm the crop before saving.</p>
                    @input(['bag' => 'default', 'id' => 'post-cover-credits', 'name' => 'cover_credits', 'label' => 'Image credits (optional)', 'placeholder' => 'Photographer or image source', 'value' => $hasOldInput ? '' : optional($post)->cover_credits, 'limit' => 120, 'required' => false])
                @endcomponent
            </div>
        </div>

        @component('admin.components.editor.section', ['id' => 'post-content', 'title' => 'Article content', 'class' => 'mb-4'])
            @tinyeditor(['bag' => 'default', 'id' => 'editor', 'name' => 'content', 'label' => 'Article content', 'labelClasses' => 'visually-hidden', 'rows' => 18, 'value' => optional($post)->content])
        @endcomponent
        @include('admin.pages.blog.post.references.layout')

        @component('admin.components.table-save-actions', ['status' => $hasOldInput ? 'Unsaved changes' : ($editing ? 'Changes take effect when you save.' : 'Not saved yet')])
            <button type="submit" id="submit-button" class="btn btn-primary">@icon($editing ? 'check' : 'plus'){{ $editing ? 'Save changes' : 'Create post' }}</button>
        @endcomponent
    </form>
    @if($editing)
        <p class="small text-muted mb-4">Created by {{ optional($post->creator)->name ?: 'Unknown creator' }}.</p>
    @endif
</div></div>
@endsection

@section('scripts')
<script src="{{ mix('js/vendor/cropper.min.js') }}"></script>
<script src="{{ mix('js/views/admin-post-editor.js') }}"></script>
@endsection
