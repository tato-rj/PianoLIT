@extends('admin.layouts.app')

@section('content')
@php($filtered = $filters['search'] !== '' || $filters['status'] !== '' || $filters['topic_id'] !== '')
<div class="content-wrapper"><div class="container-fluid">
    @component('admin.components.page.title', [
        'title' => 'Crash Courses', 'subtitle' => 'Manage courses, lessons and topics.',
    ])
        <a href="{{ route('admin.crashcourses.create', $returnFilters) }}" class="btn btn-dark btn-sm">@icon('plus')Create course</a>
        <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#crashcourse-topics-modal">@icon('tag')Manage topics</button>
    @endcomponent
    @component('admin.components.table', [
        'id' => 'crashcourses-table', 'label' => 'Crash courses', 'items' => $crashcourses,
        'action' => route('admin.crashcourses.index'), 'state' => $filters, 'sortQuery' => $returnFilters,
        'search' => ['label' => 'Find a course', 'placeholder' => 'Title, description, topic or ID'],
        'filters' => [
            ['name' => 'status', 'label' => 'Publication', 'options' => ['' => 'All statuses', 'published' => 'Published', 'draft' => 'Draft']],
            ['name' => 'topic_id', 'label' => 'Topic', 'options' => ['' => 'All topics'] + $topics->pluck('name', 'id')->all()],
        ],
        'sorts' => \App\Services\Admin\CrashCoursesTable::SORTS,
        'showReset' => $filtered || $filters['sort'] !== 'date_desc' || $filters['per_page'] !== \App\Support\AdminPagination::PER_PAGE,
        'columns' => [
            ['label' => 'Date', 'sort' => ['asc' => 'date_asc', 'desc' => 'date_desc']],
            ['label' => 'Course', 'sort' => ['asc' => 'title_asc', 'desc' => 'title_desc']],
            ['label' => 'Lessons', 'sort' => ['asc' => 'lessons_asc', 'desc' => 'lessons_desc']],
            ['label' => 'Active subscriptions', 'sort' => ['asc' => 'active_asc', 'desc' => 'active_desc']],
            ['label' => 'Publication', 'sort' => ['asc' => 'published_asc', 'desc' => 'published_desc']],
            ['label' => 'Actions', 'hidden' => true],
        ],
        'emptyTitle' => $crashcourses->total() ? 'No courses on this page' : ($filtered ? 'No matching courses' : 'No courses yet'),
        'emptyDescription' => $filtered ? 'Try another search or clear the filters.' : 'Create a course to add its lessons.',
        'emptyAction' => $crashcourses->total() ? ['label' => 'First page', 'url' => $crashcourses->url(1)] : null,
    ])
        @include('admin.pages.crashcourses.table.rows')
    @endcomponent
</div></div>
@include('admin.components.modals.delete', ['deleteDescription' => 'Delete this crash course?'])
@include('admin.pages.crashcourses.topics.modal')
@endsection

@section('scripts')
<script>
(function () {
    const modal = document.getElementById('crashcourse-topics-modal');
    modal.addEventListener('hidden.bs.modal', function () {
        const url = new URL(window.location.href);
        if (url.searchParams.get('manage') !== 'topics') return;
        url.searchParams.delete('manage');
        window.history.replaceState(null, '', url);
    });
    if (@json(request('manage')) === 'topics') window.bootstrap.Modal.getOrCreateInstance(modal).show();
})();
</script>
@endsection
