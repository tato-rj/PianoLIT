@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper"><div class="container-fluid">
    @include('admin.components.page.title', [
        'icon' => 'music', 'title' => 'Pieces',
        'subtitle' => 'Manage the repertoire, tags and featured pieces.',
        'action' => ['label' => 'Add a new piece', 'url' => route('admin.pieces.create')],
    ])
    @component('admin.components.table', [
        'id' => 'pieces-table', 'label' => 'Pieces', 'items' => $pieces,
        'description' => number_format($pieces->total()).' matching pieces.',
        'action' => route('admin.pieces.index'), 'state' => $filters,
        'hidden' => array_intersect_key($filters, array_flip(['creator_id', 'itunes', 'videos', 'score_path', 'audio_path', 'is_free'])),
        'search' => ['label' => 'Find a piece', 'placeholder' => 'Piece, composer, catalogue, tag or ID'],
        'filters' => [
            ['type' => 'checkbox-menu', 'name' => 'missing_content', 'label' => 'Missing content', 'allowEmpty' => true,
                'options' => \App\Services\Admin\PiecesTable::MISSING_CONTENT_FILTERS],
        ],
        'sorts' => array_diff_key(\App\Services\Admin\PiecesTable::SORTS, array_flip(['tags_asc', 'tags_desc'])),
        'columns' => [
            ['label' => 'Piece', 'sort' => ['asc' => 'name_asc', 'desc' => 'name_desc']],
            ['label' => 'Catalogue', 'sort' => ['asc' => 'catalogue_asc', 'desc' => 'catalogue_desc']],
            ['label' => 'Composer', 'sort' => ['asc' => 'composer_asc', 'desc' => 'composer_desc']],
            ['label' => 'Level', 'sort' => ['asc' => 'level_asc', 'desc' => 'level_desc']],
            ['label' => 'Highlighted', 'sort' => ['asc' => 'highlight_asc', 'desc' => 'highlight_desc']],
            ['label' => 'Score source', 'sort' => ['asc' => 'score_asc', 'desc' => 'score_desc']],
            ['label' => 'Actions', 'hidden' => true],
        ],
        'tableClass' => 'admin-pieces-table',
        'emptyTitle' => 'No matching pieces',
        'emptyDescription' => 'Try another search or remove a missing-content filter.',
    ])
        @include('admin.pages.pieces.table.rows')
    @endcomponent
</div></div>

@include('admin.components.modals.delete')
@include('admin.pages.pieces.schedule')
@endsection

@section('scripts')
<script src="{{ mix('js/views/admin-freepick-schedule.js') }}"></script>
@endsection
