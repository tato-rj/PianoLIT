@extends('admin.layouts.app')

@section('content')
@php
    $canEdit = auth('admin')->user()->can('update', $pianist);
    $editorSections = [
        'pianist-profile' => ['name', 'country_id', 'date_of_birth', 'date_of_death', 'cover'],
        'pianist-biography' => ['biography'],
        'pianist-music' => ['itunes_id'],
    ];
    $errorSection = null;
    foreach ($editorSections as $section => $fields) {
        if ($errors->getBag('default')->hasAny($fields)) { $errorSection = $section; break; }
    }
    $restoredSection = old('editor_section');
    $activeSection = $errorSection ?? (in_array($restoredSection, array_keys($editorSections), true) ? $restoredSection : 'pianist-profile');
@endphp
<div class="content-wrapper"><div class="container-fluid">
    @component('admin.components.page.title', [
        'title' => 'Edit '.$pianist->name,
        'breadcrumbLabel' => $pianist->name,
        'subtitle' => 'Manage the profile, biography and Apple Music albums.',
        'breadcrumbs' => [['label' => 'Pianists', 'url' => route('admin.pianists.index', $returnFilters)]],
    ])
        @if($canEdit)<button type="submit" form="edit-form" class="btn btn-dark btn-sm" data-pianist-save>@icon('check')Save changes</button>@endif
        <a href="{{ route('resources.pianists.show', $pianist) }}" target="_blank" rel="noopener noreferrer" class="btn btn-dark btn-sm">@icon('external-link')View public profile</a>
    @endcomponent
    @unless($canEdit)
        <div class="alert alert-grey">You can view this profile. Only its creator or a manager can make changes.</div>
    @endunless
    @if($errors->getBag('default')->any())
        <div class="alert alert-red" role="alert">Please check the highlighted fields. Your changes have been kept; choose the portrait again if you were replacing it.</div>
    @endif
    <form id="edit-form" method="POST" action="{{ route('admin.pianists.update', ['pianist' => $pianist] + $returnFilters) }}" enctype="multipart/form-data"
        data-pianist-editor data-error-section="{{ $errorSection }}" data-restored="{{ $errors->getBag('default')->any() ? 'true' : 'false' }}">
        @csrf
        @method('PATCH')
        <input type="hidden" name="editor_section" value="{{ $activeSection }}" data-pianist-section>
        @component('components.navigation.tab-navigation', [
            'id' => 'pianist-editor-tabs', 'label' => 'Pianist editor sections', 'variant' => 'segmented', 'active' => $activeSection,
            'items' => [
                ['target' => 'pianist-profile', 'label' => 'Profile'],
                ['target' => 'pianist-biography', 'label' => 'Biography'],
                ['target' => 'pianist-music', 'label' => 'Apple Music'],
            ],
        ])
            <div class="tab-content pt-4">
                <div class="tab-pane {{ $activeSection === 'pianist-profile' ? 'show active' : '' }}" id="pianist-profile" role="tabpanel" aria-labelledby="pianist-editor-tabs-pianist-profile" tabindex="0">
                    <div class="admin-editor-grid">
                        @component('admin.components.editor.section', ['id' => 'pianist-details', 'title' => 'Profile details', 'description' => 'The name, nationality and life dates shown on the public profile.'])
                            <fieldset @unless($canEdit) disabled @endunless>
                                @include('admin.pages.pianists.fields.profile', ['bag' => 'default', 'prefix' => 'pianist-edit'])
                            </fieldset>
                        @endcomponent
                        @component('admin.components.editor.section', ['id' => 'pianist-portrait', 'title' => 'Portrait', 'description' => 'Choose an image for this pianist.'])
                            <img src="{{ storage($pianist->cover_path) }}" alt="Portrait of {{ $pianist->name }}" width="120" height="120" class="rounded-circle object-fit-cover d-block mb-4" data-pianist-portrait>
                            @include('components.forms.upload.file', ['bag' => 'default', 'id' => 'pianist-edit-cover', 'name' => 'cover', 'label' => 'Replace portrait (optional)', 'accept' => 'image/jpeg,image/png,image/gif,image/webp', 'disabled' => !$canEdit])
                            <p class="small text-muted mb-0" role="status" aria-live="polite" data-portrait-status>JPG, PNG, GIF or WebP · up to 10 MB.</p>
                        @endcomponent
                    </div>
                </div>
                <div class="tab-pane {{ $activeSection === 'pianist-biography' ? 'show active' : '' }} mb-4" id="pianist-biography" role="tabpanel" aria-labelledby="pianist-editor-tabs-pianist-biography" tabindex="0">
                    @component('admin.components.editor.section', ['id' => 'pianist-biography-details', 'title' => 'Biography', 'description' => 'Introduce this pianist to visitors on their public profile.'])
                        <fieldset @unless($canEdit) disabled @endunless>
                            @include('admin.pages.pianists.fields.biography', ['bag' => 'default', 'prefix' => 'pianist-edit', 'rows' => 10])
                        </fieldset>
                    @endcomponent
                </div>
                <div class="tab-pane {{ $activeSection === 'pianist-music' ? 'show active' : '' }} mb-4" id="pianist-music" role="tabpanel" aria-labelledby="pianist-editor-tabs-pianist-music" tabindex="0">
                    @component('admin.components.editor.section', ['id' => 'pianist-music-details', 'title' => 'Apple Music', 'description' => 'Connect this pianist to their albums.'])
                        <fieldset @unless($canEdit) disabled @endunless>
                            @include('admin.pages.pianists.fields.music', ['bag' => 'default', 'prefix' => 'pianist-edit'])
                        </fieldset>
                        <div class="border-top mt-4 pt-4">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                                <h3 class="h5 mb-0">Album preview</h3>
                                @if($pianist->itunes_id)<button type="button" class="btn btn-dark btn-sm" data-pianist-albums-retry>Reload albums</button>@endif
                            </div>
                            <p class="small text-muted">Save changes to load albums for a different artist ID.</p>
                            <div data-pianist-albums data-artist-id="{{ $pianist->itunes_id }}">
                                <p class="text-muted mb-0" role="status" aria-live="polite" data-albums-status>{{ $pianist->itunes_id ? 'Albums load when you open this tab.' : 'Add an Apple Music artist ID and save the profile to see albums here.' }}</p>
                                <div class="row g-4 mt-0" data-albums-results></div>
                            </div>
                        </div>
                    @endcomponent
                </div>
            </div>
        @endcomponent
        @if($canEdit)
        @component('admin.components.table-save-actions', ['status' => $errors->getBag('default')->any() ? 'Unsaved changes' : 'All changes saved'])
            <button type="submit" class="btn btn-primary" data-pianist-save>@icon('check')Save changes</button>
        @endcomponent
        @endif
    </form>
    <p class="small text-muted mb-4">Created by {{ optional($pianist->creator)->name ?: 'an administrator' }}.@if($pianist->updated_at) Last updated {{ $pianist->updated_at->format('M j, Y') }}.@endif</p>
</div></div>
@endsection

@section('scripts')
<script src="{{ mix('js/views/admin-pianists.js') }}"></script>
@endsection
