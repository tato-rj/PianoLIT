@php
    $savedVideos = $piece->tutorials->map(function ($tutorial) {
        return $tutorial->only(['id', 'type', 'category', 'description', 'filename']);
    })->all();
    $videoRows = old('videos', $hasOldInput ? [] : $savedVideos);
    $videoRows = is_array($videoRows) ? array_values(array_filter($videoRows, 'is_array')) : $savedVideos;
@endphp
@component('admin.components.editor.section', ['id' => 'piece-videos', 'title' => 'Videos'])
    @slot('actions')
        <a class="btn btn-sm btn-dark" href="https://console.cloud.google.com/home/dashboard?folder=true&organizationId=true&project=pianolit" target="_blank" rel="noopener noreferrer">@icon('external-link')Video storage</a>
    @endslot
    <div data-video-rows>
        @foreach($videoRows as $index => $video)
            @include('admin.pages.pieces.edit.video-row')
        @endforeach
    </div>
    <template data-video-template>
        @include('admin.pages.pieces.edit.video-row', ['index' => '__INDEX__', 'video' => []])
    </template>
    <button type="button" class="btn btn-sm btn-dark mt-3" data-add-video>@icon('plus')Add video</button>
@endcomponent
