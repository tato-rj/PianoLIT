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
        'sorts' => array_diff_key(\App\Services\Admin\PiecesTable::SORTS, array_flip(['tags_asc', 'tags_desc'])),
        'columns' => [
            ['label' => 'ID', 'sort' => ['asc' => 'id_asc', 'desc' => 'id_desc']],
            ['label' => 'Piece', 'sort' => ['asc' => 'name_asc', 'desc' => 'name_desc']],
            ['label' => 'Composer', 'sort' => ['asc' => 'composer_asc', 'desc' => 'composer_desc']],
            ['label' => 'Level', 'sort' => ['asc' => 'level_asc', 'desc' => 'level_desc']],
            ['label' => 'Highlighted', 'sort' => ['asc' => 'highlight_asc', 'desc' => 'highlight_desc']],
            ['label' => 'Favorited', 'sort' => ['asc' => 'favorites_asc', 'desc' => 'favorites_desc']],
            ['label' => 'Actions', 'hidden' => true],
        ],
        'tableClass' => 'admin-pieces-table',
        'emptyTitle' => 'No matching pieces',
        'emptyDescription' => 'Try another search or remove a missing-content filter.',
    ])
        @slot('filterFields')
            <fieldset class="w-100 mb-0">
                <legend class="fs-6 mb-2">Missing content</legend>
                <div class="d-flex flex-wrap gap-3">
                    @foreach(['without_videos' => 'Missing video', 'without_moments' => 'Missing moments', 'without_synthesia' => 'Missing synthesia'] as $filter => $label)
                    @include('components.form.checkbox', ['id' => 'pieces-filter-'.$filter, 'name' => $filter, 'value' => '1', 'label' => $label, 'checked' => !empty($filters[$filter])])
                    @endforeach
                </div>
                <p class="small text-muted mt-2 mb-0">Results match all checked filters. Missing moments applies to performance videos.</p>
            </fieldset>
        @endslot
        @include('admin.pages.pieces.table.rows')
    @endcomponent
</div></div>

@include('admin.components.modals.delete')
@endsection
