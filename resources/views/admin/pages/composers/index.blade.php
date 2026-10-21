@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper"><div class="container-fluid">
    @include('admin.components.page.title', [
        'title' => 'Composers',
        'subtitle' => 'Find composers, update their profiles and explore their repertoire.',
        'action' => ['label' => 'Add new composer', 'modal' => 'add-modal'],
    ])
    @component('admin.components.table', [
        'id' => 'composers-table', 'label' => 'Composers', 'items' => $composers,
        'description' => number_format($composers->total()).' matching composers.',
        'action' => route('admin.composers.index'), 'state' => $filters,
        'search' => ['label' => 'Find a composer', 'placeholder' => 'Name, nationality or country'],
        'filters' => [
            ['name' => 'period', 'label' => 'Period', 'options' => ['' => 'All periods'] + $periodOptions],
            ['name' => 'famous', 'label' => 'Famous', 'options' => ['' => 'Any status', 'yes' => 'Famous', 'no' => 'Not famous']],
            ['name' => 'focus', 'label' => 'Focus', 'options' => ['' => 'All repertoire', 'pedagogical' => 'Pedagogical', 'other' => 'Other repertoire']],
        ],
        'sorts' => \App\Services\Admin\ComposersTable::SORTS,
        'resetUrl' => route('admin.composers.index'),
        'columns' => [
            ['label' => 'Composer', 'sort' => ['asc' => 'name_asc', 'desc' => 'name_desc']],
            'Period', 'Nationality', 'Famous',
            ['label' => 'Pieces', 'sort' => ['asc' => 'pieces_asc', 'desc' => 'pieces_desc']],
            ['label' => 'Actions', 'hidden' => true],
        ],
        'emptyTitle' => $composers->total() ? 'No composers on this page' : 'No matching composers',
        'emptyDescription' => 'Try another name or clear the filters.',
        'emptyAction' => ['label' => 'Show all composers', 'url' => route('admin.composers.index')],
    ])
        @include('admin.pages.composers.table.rows')
    @endcomponent
</div></div>
@include('admin.components.modals.delete', ['deleteDescription' => 'Delete this composer and every piece assigned to them?'])
@include('admin.pages.composers.create')
@endsection

@section('scripts')
<script>
$('#born-in, #died-in').inputmask('99/99/9999');
</script>
@endsection
