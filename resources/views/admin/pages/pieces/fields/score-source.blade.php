@php($scoreSourceBag = 'scoreSource'.$piece->id)
@component('admin.components.editor.section', ['id' => 'piece-score-source', 'sectionId' => 'piece-score-source', 'title' => 'Score source', 'class' => 'mt-4'])
    <form method="POST" action="{{ route('admin.pieces.update-score-source', $piece) }}">
        @csrf
        @method('PATCH')
        <input type="hidden" name="return_to_editor" value="1">
        <input type="hidden" name="score_version" value="{{ hash('sha256', $piece->score_path) }}">
        <div class="row g-3">
            @select(['bag' => $scoreSourceBag, 'id' => 'piece-score-source-choice', 'name' => 'score_source', 'label' => 'Source',
                'choices' => \App\Services\Pieces\ScoreSource::LABELS, 'value' => $piece->score_source ?? 'unknown', 'grid' => 'col-md-4',
                'useOldInput' => $errors->getBag($scoreSourceBag)->any()])
            @input(['bag' => $scoreSourceBag, 'id' => 'piece-score-source-url', 'name' => 'score_source_url', 'label' => 'Source link (optional)',
                'type' => 'url', 'placeholder' => 'https://', 'limit' => 2048, 'required' => false, 'value' => $piece->score_source_url, 'grid' => 'col-md-8',
                'useOldInput' => $errors->getBag($scoreSourceBag)->any()])
        </div>
        <button type="submit" class="btn btn-sm btn-primary mt-2">Save score source</button>
    </form>
@endcomponent
