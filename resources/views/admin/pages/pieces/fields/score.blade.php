@component('admin.components.editor.section', ['id' => 'piece-score', 'title' => 'Score & audio'])
    <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
        <span>Public-domain score</span>
        @toggle(['toggle' => $isPublic, 'name' => 'is_public', 'autoToggle' => false, 'ariaLabel' => 'Public-domain score'])
    </div>
    <div class="row g-3" data-public-fields @if(!$isPublic) hidden @endif>
        @input(['bag' => 'default', 'id' => 'piece-score-editor', 'name' => 'score_editor', 'label' => 'Score editor', 'value' => $piece->score_editor == $piece->missing_info ? null : $piece->score_editor, 'required' => false, 'grid' => 'col-sm-6'])
        @input(['bag' => 'default', 'id' => 'piece-score-publisher', 'name' => 'score_publisher', 'label' => 'Score publisher', 'value' => $piece->score_publisher == $piece->missing_info ? null : $piece->score_publisher, 'required' => false, 'grid' => 'col-sm-6'])
    </div>
    <div data-external-score @if($isPublic) hidden @endif>
        @input(['bag' => 'default', 'id' => 'piece-score-url', 'name' => 'score_url', 'label' => 'Score URL', 'value' => $piece->score_url, 'required' => false, 'limit' => 255, 'placeholder' => 'https://'])
        @if($piece->score_url)<a class="small d-inline-block mb-3" href="{{ $piece->score_url }}" target="_blank" rel="noopener noreferrer">@icon('external-link')Open current score link</a>@endif
    </div>
    <div class="row g-3">
        @include('components.forms.upload.file', ['id' => 'piece-audio', 'name' => 'audio', 'label' => 'Audio · both hands', 'accept' => 'audio/*', 'currentUrl' => storage($piece->audio_path), 'currentLabel' => 'Open current audio', 'grid' => 'col-sm-6'])
        @include('components.forms.upload.file', ['id' => 'piece-score-file', 'name' => 'score', 'label' => 'Score · PDF', 'accept' => '.pdf', 'currentUrl' => storage($piece->score_path), 'currentLabel' => 'Open current score', 'grid' => 'col-sm-6'])
    </div>
    <div class="d-flex justify-content-between align-items-center gap-3 mt-2">
        <span>Show this piece in the tour</span>
        @toggle(['toggle' => (bool) old('show_on_tour', $hasOldInput ? false : $piece->show_on_tour), 'name' => 'show_on_tour', 'autoToggle' => false, 'ariaLabel' => 'Show this piece in the tour'])
    </div>
    @can('update', $piece)
    @if($piece->score_path)
        <button type="submit" form="delete-score-form" class="btn btn-sm btn-danger mt-4">@icon('trash-2')Delete score</button>
    @endif
    @endcan
@endcomponent
