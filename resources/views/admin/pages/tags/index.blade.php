@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper"><div class="container-fluid">
    @include('admin.components.page.title', [
        'title' => 'Tags',
        'subtitle' => 'Organize piece tags by type and see where they are used.',
        'action' => ['label' => 'Create a tag', 'modal' => 'tag-modal'],
    ])
    @component('admin.components.table', [
        'id' => 'tags-table', 'label' => 'Tags', 'items' => $tags,
        'description' => number_format($tags->total()).' matching '.str_plural('tag', $tags->total()).'.',
        'action' => route('admin.tags.index'), 'state' => $filters,
        'search' => ['label' => 'Find a tag', 'placeholder' => 'Tag name or type'],
        'filters' => [['name' => 'tag_type', 'label' => 'Type', 'options' => ['' => 'All types'] + $tagTypes]],
        'sorts' => \App\Services\Admin\TagsTable::SORTS,
        'showReset' => $filters['search'] !== '' || $filters['tag_type'] !== '' || $filters['sort'] !== 'type_asc' || $filters['per_page'] !== \App\Support\AdminPagination::PER_PAGE,
        'resetUrl' => route('admin.tags.index'),
        'columns' => [
            ['label' => 'Tag', 'sort' => ['asc' => 'name_asc', 'desc' => 'name_desc']],
            ['label' => 'Type', 'sort' => ['asc' => 'type_asc', 'desc' => 'type_desc']],
            ['label' => 'Pieces', 'sort' => ['asc' => 'pieces_asc', 'desc' => 'pieces_desc']],
            ['label' => 'Display order', 'sort' => ['asc' => 'ordering_asc', 'desc' => 'ordering_desc']],
            ['label' => 'Actions', 'hidden' => true],
        ],
        'emptyTitle' => $tags->total() ? 'No tags on this page' : 'No matching tags',
        'emptyDescription' => 'Try another name or type, or create a tag to get started.',
        'emptyAction' => ['label' => 'Show all tags', 'url' => route('admin.tags.index')],
    ])
        @foreach($tags as $tag)
            @include('admin.pages.tags.tag')
        @endforeach
    @endcomponent
</div></div>
@include('admin.components.modals.tag')
@include('admin.pages.tags.delete')
@endsection

@section('scripts')
<script src="{{ mix('js/views/admin-tags.js') }}"></script>
@endsection
