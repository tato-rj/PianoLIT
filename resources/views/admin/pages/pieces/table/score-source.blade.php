@php
    $scoreSourceLabels = \App\Services\Pieces\ScoreSource::LABELS;
    $scoreSourceBag = 'scoreSource'.$item->id;
@endphp
@if(!$item->score_path)
    <span class="text-muted">No PDF</span>
@elseif(auth('admin')->user()->isManager())
    <form class="d-flex align-items-start gap-2" method="POST" action="{{ route('admin.pieces.update-score-source', ['piece' => $item] + $returnFilters) }}">
        @csrf
        @method('PATCH')
        <input type="hidden" name="score_version" value="{{ hash('sha256', $item->score_path) }}">
        @select(['bag' => $scoreSourceBag, 'name' => 'score_source', 'id' => 'table-score-source-'.$item->id,
            'label' => 'Score source for '.$item->long_name, 'labelClasses' => 'visually-hidden', 'classes' => 'form-control-sm', 'grid' => 'mb-0',
            'choices' => $scoreSourceLabels, 'value' => $item->score_source ?? 'unknown', 'useOldInput' => $errors->getBag($scoreSourceBag)->any()])
        <button type="submit" class="btn btn-sm btn-outline-secondary" aria-label="Save score source for {{ $item->long_name }}">Save</button>
    </form>
@else
    {{ $scoreSourceLabels[$item->score_source] ?? $scoreSourceLabels['unknown'] }}
@endif
