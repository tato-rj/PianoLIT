@php
    $audio = $type === 'audio';
    $accept = $audio ? '.mp3,.wav,.ogg,.m4a' : '.jpg,.jpeg,.png,.webp,.gif';
    $hint = $audio ? 'MP3, WAV, OGG or M4A · up to 2 MB' : 'JPG, PNG, WebP or GIF · up to 5 MB';
    $mediaReturnFilters = $returnFilters + ['media_page' => $files->currentPage()];
@endphp
@component('components.dialogs.modal', ['id' => 'quiz-manage-modal', 'header' => $audio ? 'Manage audio' : 'Manage images'])
@slot('body')
    <form method="POST" enctype="multipart/form-data" action="{{ route('admin.quizzes.media.store', ['type' => $type] + $returnFilters) }}" class="mb-3">
        @csrf
        @include('components.forms.upload.dropzone', ['id' => 'quiz-media-upload', 'name' => 'files[]', 'bag' => 'quizMediaUpload', 'label' => $audio ? 'Upload audio' : 'Upload images', 'compact' => true, 'required' => true, 'multiple' => true, 'maxFiles' => 8, 'accept' => $accept, 'maxBytes' => $audio ? 2097152 : 5242880, 'hint' => $hint.' · up to 8 files', 'fileLabel' => $audio ? 'audio file' : 'image', 'filePluralLabel' => $audio ? 'audio files' : 'images'])
        <button type="submit" class="btn btn-primary btn-sm">@icon('upload')Upload</button>
    </form>
    <div class="d-flex flex-column">
    @forelse($files as $file)
        @php($bag = 'quizMedia'.md5($file['path']))
        <div class="py-2 border-top">
            <div class="d-flex align-items-center gap-2">
                @unless($audio)
                <a href="{{ storage($file['path']) }}" target="_blank" rel="noopener noreferrer" aria-label="View {{ $file['name'] }}"><img src="{{ storage($file['path']) }}" alt="" width="48" height="48" class="rounded object-fit-contain" loading="lazy"></a>
                @endunless
                <div class="flex-grow-1 text-break small">{{ $file['name'] }}</div>
                <div class="d-flex flex-shrink-0 align-items-center gap-1">
                    @include('admin.components.table-action', ['tableAction' => ['label' => 'Copy path for '.$file['name'], 'icon' => 'copy', 'iconOnly' => true, 'size' => 'xs', 'attributes' => ['data-quiz-copy' => '/storage/'.$file['path']]]])
                    @include('admin.components.table-action', ['tableAction' => ['kind' => 'edit', 'label' => 'Replace '.$file['name'], 'iconOnly' => true, 'size' => 'xs', 'attributes' => ['data-bs-toggle' => 'collapse', 'data-bs-target' => '#quiz-media-'.md5($file['path']), 'aria-expanded' => $errors->getBag($bag)->any() ? 'true' : 'false', 'aria-controls' => 'quiz-media-'.md5($file['path'])]]])
                    <form method="POST" action="{{ route('admin.quizzes.media.destroy', ['path' => $file['path']] + $mediaReturnFilters) }}" class="m-0" onsubmit="return window.confirm('Delete this file? Quiz questions using it will no longer display or play it.')">
                        @csrf @method('DELETE')
                        @include('admin.components.table-action', ['tableAction' => ['kind' => 'delete', 'type' => 'submit', 'label' => 'Delete '.$file['name'], 'size' => 'xs']])
                    </form>
                </div>
            </div>
            @if($audio)<audio controls preload="none" src="{{ storage($file['path']) }}" class="w-100 mt-1" aria-label="Preview {{ $file['name'] }}"></audio>@endif
            <div id="quiz-media-{{ md5($file['path']) }}" class="collapse {{ $errors->getBag($bag)->any() ? 'show' : '' }}">
                <form method="POST" enctype="multipart/form-data" action="{{ route('admin.quizzes.media.update', ['path' => $file['path']] + $mediaReturnFilters) }}" class="mt-2">
                    @csrf @method('PATCH')
                    @include('components.forms.upload.dropzone', ['id' => 'quiz-media-replace-'.md5($file['path']), 'name' => 'file', 'bag' => $bag, 'label' => 'Replace file', 'compact' => true, 'required' => true, 'accept' => '.'.pathinfo($file['name'], PATHINFO_EXTENSION), 'maxBytes' => $audio ? 2097152 : 5242880, 'hint' => 'Use the same format. The saved path will stay the same.', 'fileLabel' => $audio ? 'audio file' : 'image', 'filePluralLabel' => $audio ? 'audio files' : 'images'])
                    <button type="submit" class="btn btn-primary btn-sm">Save replacement</button>
                </form>
            </div>
        </div>
    @empty
        <p class="text-muted mb-0">{{ $audio ? 'No audio files yet.' : 'No images yet.' }}</p>
    @endforelse
    </div>
    @include('admin.components.pagination', ['paginator' => $files, 'label' => $audio ? 'Quiz audio' : 'Quiz images'])
    <input type="text" class="form-control" data-copy-fallback aria-label="File path" readonly hidden>
@endslot
@endcomponent
<template id="quiz-copy-success">@include('components.feedback.alert', ['color' => 'green', 'message' => 'File path copied.', 'dismissible' => true, 'floating' => 'top', 'temporary' => true])</template>
<template id="quiz-copy-error">@include('components.feedback.alert', ['color' => 'red', 'message' => 'The file path could not be copied. Try again.', 'dismissible' => true, 'floating' => 'top'])</template>
