@extends('admin.layouts.app')

@section('content')
@php($filtered = $filters['search'] !== '' || !empty($filters['status']) || !empty($filters['topic']))
<div class="content-wrapper"><div class="container-fluid">
    @component('admin.components.page.title', [
        'title' => 'Posts', 'subtitle' => 'Manage blog posts, audio and topics.',
    ])
        <a href="{{ route('admin.posts.create', $returnFilters) }}" class="btn btn-sm btn-dark">@icon('plus')Create post</a>
        <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#blog-audio-modal">@icon('music')Manage audio</button>
        @can('create', \App\Blog\Topic::class)
        <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#blog-topics-modal">@icon('tag')Manage topics</button>
        @endcan
    @endcomponent
    @component('admin.components.table', [
        'id' => 'posts-table', 'label' => 'Posts', 'items' => $posts,
        'action' => route('admin.posts.index'), 'state' => $filters,
        'search' => ['label' => 'Find a post', 'placeholder' => 'Title, description, content or ID'],
        'filters' => [
            ['name' => 'status', 'label' => 'Publication', 'options' => ['' => 'All statuses', 'published' => 'Published', 'draft' => 'Draft']],
            ['name' => 'topic', 'label' => 'Topic', 'options' => ['' => 'All topics'] + $topics->pluck('name', 'id')->all()],
        ],
        'sorts' => \App\Services\Admin\PostsTable::SORTS,
        'showReset' => $filtered || $filters['sort'] !== 'date_desc' || $filters['per_page'] !== \App\Support\AdminPagination::PER_PAGE,
        'columns' => [
            ['label' => 'Date', 'sort' => ['asc' => 'date_asc', 'desc' => 'date_desc']],
            ['label' => 'Post', 'sort' => ['asc' => 'title_asc', 'desc' => 'title_desc']],
            ['label' => 'Reading time', 'sort' => ['asc' => 'reading_asc', 'desc' => 'reading_desc']],
            ['label' => 'Publication', 'sort' => ['asc' => 'published_asc', 'desc' => 'published_desc']],
            ['label' => 'Actions', 'hidden' => true],
        ],
        'emptyTitle' => $posts->total() ? 'No posts on this page' : ($filtered ? 'No matching posts' : 'No posts yet'),
        'emptyDescription' => $filtered ? 'Try another search or clear the filters.' : 'Create a post to add it to the blog.',
        'emptyAction' => $posts->total() ? ['label' => 'First page', 'url' => $posts->url(1)] : null,
    ])
        @include('admin.pages.blog.table.rows')
    @endcomponent
</div></div>
@include('admin.components.modals.delete', ['deleteDescription' => 'Delete this blog post?'])
@can('create', \App\Blog\Topic::class)
@include('admin.pages.blog.topics.modal')
@endcan
@include('admin.pages.blog.audio.modal')
@endsection

@section('scripts')
<script>
(function () {
    document.querySelectorAll('#blog-audio-modal input[readonly]').forEach(function (input) {
        input.addEventListener('click', function () { input.select(); });
    });
    document.querySelectorAll('#blog-audio-modal audio').forEach(function (audio) {
        audio.addEventListener('play', function () {
            document.querySelectorAll('#blog-audio-modal audio').forEach(function (other) { if (other !== audio) other.pause(); });
        });
    });
    ['topics', 'audio'].forEach(function (kind) {
        const modal = document.getElementById('blog-' + kind + '-modal');
        if (!modal) return;
        modal.addEventListener('hidden.bs.modal', function () {
            modal.querySelectorAll('audio').forEach(function (audio) { audio.pause(); });
            const url = new URL(window.location.href);
            if (url.searchParams.get('manage') !== kind) return;
            url.searchParams.delete('manage');
            window.history.replaceState(null, '', url);
        });
    });
    const kind = @json(request('manage'));
    const modal = document.getElementById('blog-' + kind + '-modal');
    if (modal) window.bootstrap.Modal.getOrCreateInstance(modal).show();
})();
</script>
@endsection
