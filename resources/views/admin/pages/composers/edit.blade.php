@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper"><div class="container-fluid">
    @component('admin.components.page.title', [
        'theme' => 'edit', 'title' => $composer->name,
        'subtitle' => 'Manage the profile, biography and repertoire.',
        'breadcrumbs' => [['label' => 'Composers', 'url' => route('admin.composers.index', $returnFilters)]],
    ])
        <a href="#composer-profile" class="btn btn-sm btn-secondary">Profile</a>
        <a href="#composer-biography" class="btn btn-sm btn-secondary">Biography</a>
        <a href="#composer-repertoire" class="btn btn-sm btn-dark">@icon('music')Repertoire ({{ number_format($pieces->total()) }})</a>
    @endcomponent
    @cannot('update', $composer)<p class="alert alert-grey">You can view this profile. Changes can be saved by its creator or a manager.</p>@endcannot
    <form id="edit-form" method="POST" action="{{ route('admin.composers.update', ['composer' => $composer] + $returnFilters) }}" enctype="multipart/form-data">
        @csrf
        @method('PATCH')
        <fieldset class="row g-4 mb-4" @cannot('update', $composer) disabled @endcannot>
            <div class="col-xl-6">
            @component('admin.components.editor.section', ['id' => 'composer-profile', 'sectionId' => 'composer-profile', 'title' => 'Profile', 'description' => 'The details that identify this composer.'])
                <div class="mb-4">@include('components.composer-label')</div>
                @include('admin.pages.composers.fields.profile', ['bag' => 'default', 'prefix' => 'composer-edit'])
                @include('admin.pages.composers.fields.options')
            @endcomponent
            </div>
            <div class="col-xl-6">
            @component('admin.components.editor.section', ['id' => 'composer-biography', 'sectionId' => 'composer-biography', 'title' => 'Biography', 'description' => 'Introduce the composer and share an interesting fact.'])
                @include('admin.pages.composers.fields.biography', ['bag' => 'default', 'prefix' => 'composer-edit'])
            @endcomponent
            </div>
        </fieldset>
        @can('update', $composer)
        @component('admin.components.table-save-actions', ['status' => 'Changes take effect when you save.'])
            <button type="submit" class="btn btn-primary">@icon('check')Save changes</button>
        @endcomponent
        @endcan
        <p class="small text-muted mb-4">Created by {{ optional($composer->creator)->name ?: 'Unknown creator' }}.</p>
    </form>
    <div id="composer-repertoire">
        @component('admin.components.table', [
            'id' => 'composer-pieces-table', 'title' => 'Repertoire', 'label' => 'Composer repertoire', 'items' => $pieces,
            'description' => number_format($pieces->total()).' '.str_plural('piece', $pieces->total()).' by '.$composer->name.'. Open a title to view its piece page.',
            'pageSizes' => [], 'columns' => ['Piece', ['label' => 'Actions', 'hidden' => true]],
            'emptyTitle' => 'No pieces yet', 'emptyDescription' => 'Pieces assigned to this composer will appear here.',
        ])
            @foreach($pieces as $piece)
            <tr>
                <td><a href="{{ route('webapp.pieces.show', $piece) }}" class="admin-table__primary" target="_blank" rel="noopener noreferrer">{{ $piece->long_name }}</a></td>
                <td>@include('admin.components.table-action', ['tableAction' => ['kind' => 'edit', 'url' => route('admin.pieces.edit', $piece), 'attributes' => ['aria-label' => 'Edit '.$piece->long_name]]])</td>
            </tr>
            @endforeach
        @endcomponent
    </div>
</div></div>
@endsection

@section('scripts')
<script>
$('#born-in, #died-in').inputmask('99/99/9999');
</script>
@endsection
