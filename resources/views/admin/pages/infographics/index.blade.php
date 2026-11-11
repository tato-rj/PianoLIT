@extends('admin.layouts.app')

@section('content')
@php($filtered = $filters['search'] !== '' || $filters['status'] || $filters['topic_id'])
<div class="content-wrapper"><div class="container-fluid">
    @component('admin.components.page.title', [
        'title' => 'Infographics', 'subtitle' => 'Manage infographics, topics and publication.',
        'action' => ['label' => 'Create infographic', 'url' => route('admin.infographs.create', $returnFilters)],
    ])
        <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#infographic-topics-modal">@icon('tag')Manage topics</button>
    @endcomponent
    @component('admin.components.table', [
        'id' => 'infographs-table', 'label' => 'Infographics', 'items' => $infographs,
        'action' => route('admin.infographs.index'), 'state' => $filters,
        'search' => ['label' => 'Find an infographic', 'placeholder' => 'Name, description, topic or ID'],
        'filters' => [
            ['name' => 'status', 'label' => 'Publication', 'options' => ['' => 'All statuses', 'published' => 'Published', 'draft' => 'Draft']],
            ['name' => 'topic_id', 'label' => 'Topic', 'options' => ['' => 'All topics'] + $topics->pluck('name', 'id')->all()],
        ],
        'sorts' => \App\Services\Admin\InfographicsTable::SORTS,
        'showReset' => $filtered || $filters['sort'] !== 'date_desc' || $filters['per_page'] !== \App\Support\AdminPagination::PER_PAGE,
        'columns' => [
            ['label' => 'Date', 'sort' => ['asc' => 'date_asc', 'desc' => 'date_desc']],
            ['label' => 'Infographic', 'sort' => ['asc' => 'name_asc', 'desc' => 'name_desc']],
            ['label' => 'Downloads', 'sort' => ['asc' => 'downloads_asc', 'desc' => 'downloads_desc']],
            ['label' => 'Score', 'sort' => ['asc' => 'score_asc', 'desc' => 'score_desc']],
            'Published', ['label' => 'Actions', 'hidden' => true],
        ],
        'emptyTitle' => $infographs->total() ? 'No infographics on this page' : ($filtered ? 'No matching infographics' : 'No infographics yet'),
        'emptyDescription' => $filtered ? 'Try another search or clear the filters.' : 'Create an infographic to add it to the website.',
        'emptyAction' => $infographs->total() ? ['label' => 'First page', 'url' => $infographs->url(1)] : null,
    ])
        @include('admin.pages.infographics.table.rows')
    @endcomponent
</div></div>
@include('admin.components.modals.delete', ['deleteDescription' => 'Delete this infographic?'])
@include('admin.pages.infographics.topics.modal')
@include('admin.pages.infographics.preview')
@endsection

@section('scripts')
<script>
(function () {
    const topics = document.getElementById('infographic-topics-modal');
    const preview = document.getElementById('item-preview');
    preview.addEventListener('show.bs.modal', function (event) {
        const trigger = event.relatedTarget;
        if (!trigger) return;
        preview.querySelector('img.image').src = trigger.dataset.image;
        preview.querySelector('img.image').alt = trigger.dataset.name || 'Infographic';
        preview.querySelector('img.thumbnail').src = trigger.dataset.thumbnail;
    });
    ['image', 'thumbnail'].forEach(function (kind) {
        const image = preview.querySelector('img.' + kind);
        const dimensions = preview.querySelector('[data-dimensions="' + kind + '"]');
        image.addEventListener('load', function () {
            dimensions.textContent = image.naturalWidth + ' × ' + image.naturalHeight + ' px';
        });
        image.addEventListener('error', function () { dimensions.textContent = 'Unavailable'; });
    });
    preview.addEventListener('hidden.bs.modal', function () {
        preview.querySelectorAll('img').forEach(function (image) { image.removeAttribute('src'); });
        preview.querySelectorAll('[data-dimensions]').forEach(function (label) { label.textContent = ''; });
    });
    topics.addEventListener('hidden.bs.modal', function () {
        const url = new URL(window.location.href);
        if (!url.searchParams.has('topics')) return;
        url.searchParams.delete('topics');
        window.location.assign(url);
    });
    @if(request()->query('topics') === '1')
    window.bootstrap.Modal.getOrCreateInstance(topics).show();
    @endif
})();
</script>
@endsection
