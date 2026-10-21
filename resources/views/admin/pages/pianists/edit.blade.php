@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper"><div class="container-fluid">
    @include('admin.components.page.title', [
        'title' => 'Edit '.$pianist->name,
        'breadcrumbLabel' => $pianist->name,
        'subtitle' => 'Update this pianist’s profile and portrait.',
        'breadcrumbs' => [['label' => 'Pianists', 'url' => route('admin.pianists.index', $returnFilters)]],
        'action' => ['label' => 'View public profile', 'external_link' => route('resources.pianists.show', $pianist)],
    ])
    @cannot('update', $pianist)
        <div class="alert alert-grey">You can view this profile. Only its creator or a manager can make changes.</div>
    @endcannot
    <form id="edit-form" method="POST" action="{{ route('admin.pianists.update', ['pianist' => $pianist] + $returnFilters) }}" enctype="multipart/form-data">
        @csrf
        @method('PATCH')
        <div class="admin-editor-grid">
            <div class="admin-editor-stack">
                @component('admin.components.editor.section', ['id' => 'pianist-profile', 'title' => 'Profile details', 'description' => 'Biography, nationality and life dates shown on the public profile.'])
                    <fieldset @cannot('update', $pianist) disabled @endcannot>
                        @include('admin.pages.pianists.fields', ['bag' => 'default', 'prefix' => 'pianist-edit'])
                    </fieldset>
                @endcomponent
            </div>
            <div class="admin-editor-stack">
                @component('admin.components.editor.section', ['id' => 'pianist-portrait', 'title' => 'Portrait', 'description' => 'Choose an image for the pianist’s profile.'])
                    <img src="{{ storage($pianist->cover_path) }}" alt="Portrait of {{ $pianist->name }}" width="120" height="120" class="rounded-circle object-fit-cover d-block mb-4">
                    @include('components.form.upload.file', ['bag' => 'default', 'id' => 'pianist-edit-cover', 'name' => 'cover', 'label' => 'Replace portrait (optional)', 'accept' => 'image/jpeg,image/png,image/gif,image/webp', 'disabled' => !auth('admin')->user()->can('update', $pianist)])
                    <p class="small text-muted mb-0">Created by {{ optional($pianist->creator)->name ?: 'an administrator' }}.</p>
                @endcomponent
            </div>
        </div>
        @can('update', $pianist)
        <div class="admin-editor-save mb-4">
            <p class="small text-muted mb-0">Changes appear on the public profile when saved.</p>
            <button type="submit" class="btn btn-primary">@icon('check')Save changes</button>
        </div>
        @endcan
    </form>
    @component('admin.components.editor.section', ['id' => 'pianist-albums', 'title' => 'Apple Music albums', 'description' => 'Albums from the saved artist ID. Save changes to preview a different artist.'])
        @slot('actions')
            @if($pianist->itunes_id)<button type="button" class="btn btn-dark btn-sm" data-pianist-albums-retry>Reload albums</button>@endif
        @endslot
        <div data-pianist-albums data-artist-id="{{ $pianist->itunes_id }}">
            <p class="text-muted mb-0" role="status" aria-live="polite" data-albums-status>{{ $pianist->itunes_id ? 'Loading albums…' : 'Add an Apple Music artist ID and save the profile to see albums here.' }}</p>
            <div class="row g-4 mt-0" data-albums-results></div>
        </div>
    @endcomponent
</div></div>
@endsection

@section('scripts')
<script src="{{ mix('js/views/admin-pianists.js') }}"></script>
@endsection
