@extends('admin.layouts.app')

@section('content')
@php($filtered = $filters['search'] !== '' || $filters['status'] !== '' || $filters['topic_id'] !== '' || $filters['level'] !== '')
<div class="content-wrapper"><div class="container-fluid">
    @component('admin.components.page.title', ['title' => 'Quizzes', 'subtitle' => 'Manage quizzes, topics and question media.'])
        <a href="{{ route('admin.quizzes.create', $returnFilters) }}" class="btn btn-dark btn-sm">@icon('plus')Create quiz</a>
        @foreach(['audio' => ['volume-2', 'Manage audio'], 'topics' => ['tag', 'Manage topics'], 'images' => ['image', 'Manage images']] as $section => $action)
        <a href="{{ route('admin.quizzes.index', $returnFilters + ['manage' => $section]) }}" class="btn btn-secondary btn-sm">@icon($action[0]){{ $action[1] }}</a>
        @endforeach
    @endcomponent
    @component('admin.components.table', [
        'id' => 'quizzes-table', 'label' => 'Quizzes', 'items' => $quizzes,
        'action' => route('admin.quizzes.index'), 'state' => $filters,
        'search' => ['label' => 'Find a quiz', 'placeholder' => 'Title, description or ID'],
        'filters' => [
            ['name' => 'status', 'label' => 'Publication', 'options' => ['' => 'All statuses', 'published' => 'Published', 'draft' => 'Draft']],
            ['name' => 'topic_id', 'label' => 'Topic', 'options' => ['' => 'All topics'] + $topics->pluck('name', 'id')->all()],
            ['name' => 'level', 'label' => 'Level', 'options' => ['' => 'All levels'] + $levels->pluck('name', 'id')->all()],
        ],
        'sorts' => \App\Services\Admin\QuizzesTable::SORTS,
        'showReset' => $filtered || $filters['sort'] !== 'date_desc' || $filters['per_page'] !== \App\Support\AdminPagination::PER_PAGE,
        'columns' => [
            ['label' => 'Date', 'sort' => ['asc' => 'date_asc', 'desc' => 'date_desc']],
            ['label' => 'Title', 'sort' => ['asc' => 'title_asc', 'desc' => 'title_desc']],
            'Questions',
            ['label' => 'Published', 'sort' => ['asc' => 'published_asc', 'desc' => 'published_desc']],
            ['label' => 'Actions', 'hidden' => true],
        ],
        'emptyTitle' => $quizzes->total() ? 'No quizzes on this page' : ($filtered ? 'No matching quizzes' : 'No quizzes yet'),
        'emptyDescription' => $filtered ? 'Try another search or clear the filters.' : 'Create a quiz to add it here.',
        'emptyAction' => $quizzes->total() ? ['label' => 'First page', 'url' => $quizzes->url(1)] : null,
    ])
        @include('admin.pages.quizzes.table.rows')
    @endcomponent
</div></div>
@include('admin.components.modals.delete')
@if($manage === 'topics')
    @include('admin.pages.quizzes.topics.modal')
@elseif($files)
    @include('admin.pages.quizzes.media.modal', ['type' => $manage])
@endif
@endsection

@section('scripts')
@if($manage)
<script>
(function () {
    const modal = document.getElementById('quiz-manage-modal');
    modal.addEventListener('hide.bs.modal', function () {
        modal.querySelectorAll('audio').forEach(function (audio) { audio.pause(); });
    });
    modal.addEventListener('hidden.bs.modal', function () {
        window.location.assign(@json(route('admin.quizzes.index', $returnFilters)));
    });
    modal.querySelectorAll('[data-quiz-copy]').forEach(function (button) {
        button.addEventListener('click', async function () {
            let copied = false;
            try {
                await navigator.clipboard.writeText(button.dataset.quizCopy);
                copied = true;
            } catch (error) {
                const field = modal.querySelector('[data-copy-fallback]');
                field.value = button.dataset.quizCopy;
                field.hidden = false;
                field.select();
                try { copied = document.execCommand('copy'); } catch (failure) {}
                field.hidden = true;
                button.focus();
            }
            document.querySelectorAll('.alert-container.alert-top').forEach(function (alert) { alert.remove(); });
            const template = document.getElementById(copied ? 'quiz-copy-success' : 'quiz-copy-error');
            document.body.appendChild(template.content.cloneNode(true));
        });
    });
    window.bootstrap.Modal.getOrCreateInstance(modal).show();
})();
</script>
@endif
@endsection
