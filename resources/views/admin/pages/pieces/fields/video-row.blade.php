@php
    $categories = ['performance' => 'Performance', 'slow' => 'Slow performance', 'tutorial' => 'Tutorial', 'synthesia' => 'Synthesia', 'harmony' => 'Harmonic analysis'];
    if (!empty($video['category']) && !isset($categories[$video['category']])) $categories[$video['category']] = $video['type'] ?? $video['category'];
    $savedTutorial = isset($video['id']) ? $piece->tutorials->firstWhere('id', $video['id']) : null;
@endphp
<div class="admin-editor-video bg-white rounded-sm shadow p-3 border-0" data-video-row data-video-index="{{ $index }}">
    @if($savedTutorial)<input type="hidden" name="videos[{{ $index }}][id]" value="{{ $savedTutorial->id }}">@endif
    <input type="hidden" name="videos[{{ $index }}][type]" value="{{ $video['type'] ?? '' }}" data-video-type>
    <div class="row g-3">
        @select(['bag' => 'default', 'id' => 'piece-video-'.$index.'-category', 'name' => 'videos['.$index.'][category]', 'label' => 'Video type', 'choices' => $categories, 'value' => $video['category'] ?? '', 'placeholder' => 'Choose a type', 'grid' => 'col-sm-5'])
        @input(['bag' => 'default', 'id' => 'piece-video-'.$index.'-filename', 'name' => 'videos['.$index.'][filename]', 'label' => 'File name', 'value' => $video['filename'] ?? '', 'grid' => 'col-sm-7', 'required' => false, 'placeholder' => 'Video file name'])
        @input(['bag' => 'default', 'id' => 'piece-video-'.$index.'-description', 'name' => 'videos['.$index.'][description]', 'label' => 'Description', 'value' => $video['description'] ?? '', 'grid' => 'col-12', 'required' => false, 'placeholder' => 'Short video description'])
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
        @if($savedTutorial)
            <a class="btn btn-sm btn-dark" href="{{ $savedTutorial->video_url }}" target="_blank" rel="noopener noreferrer">@icon('play')View video</a>
            @if($savedTutorial->type === 'Performance')
                <a class="btn btn-sm btn-dark" href="{{ route('admin.pieces.videos.moments.edit', [$piece, $savedTutorial]) }}">@icon('tag')Manage sections</a>
            @endif
        @endif
        <button type="button" class="btn btn-sm btn-danger ms-auto" data-remove-video>@icon('trash-2')Remove video</button>
    </div>
</div>
