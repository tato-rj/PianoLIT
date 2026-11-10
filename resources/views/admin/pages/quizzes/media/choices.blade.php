@include('admin.components.filters.form', [
    'id' => 'quiz-media-search', 'label' => 'Media library filters', 'action' => route('admin.quizzes.media.choose', $type),
    'class' => 'mb-3', 'fields' => [['type' => 'search', 'name' => 'search', 'label' => 'Find a file', 'placeholder' => 'File name']],
    'state' => ['search' => $search], 'buttons' => [['label' => 'Search']],
    'resetUrl' => $search !== '' ? route('admin.quizzes.media.choose', $type) : null,
])
<div class="d-flex flex-column gap-2">
@forelse($files as $file)
    @php($reference = app(\App\Services\Admin\QuizQuestionMedia::class)->reference($file['path']))
    <div class="border-top pt-2">
        <div class="d-flex align-items-center gap-2">
            @if($type === 'images')<img src="{{ asset($reference) }}" alt="" width="48" height="48" loading="lazy" class="rounded object-fit-contain">@endif
            <span class="small flex-grow-1 text-break">{{ $file['name'] }}</span>
            @include('admin.components.table-action', ['tableAction' => ['label' => 'Use '.$file['name'], 'icon' => 'check', 'size' => 'xs', 'iconOnly' => true, 'attributes' => ['data-select-quiz-media' => true, 'data-path' => $reference, 'data-name' => $file['name'], 'data-url' => asset($reference), 'data-type' => $type]]])
        </div>
        @if($type === 'audio')<audio controls preload="none" src="{{ asset($reference) }}" class="w-100 mt-1" aria-label="Preview {{ $file['name'] }}"></audio>@endif
    </div>
@empty
    <p class="text-muted mb-0">{{ $search !== '' ? 'No files match this search.' : ($type === 'audio' ? 'No audio files yet.' : 'No images yet.') }}</p>
    @if($search === '')<p class="small text-muted mb-0">Upload files from the Quizzes page.</p>@endif
@endforelse
</div>
@include('admin.components.pagination', ['paginator' => $files, 'label' => 'Media library'])
