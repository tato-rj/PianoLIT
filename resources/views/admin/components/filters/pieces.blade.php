<div class="d-flex flex-wrap gap-2 mb-3">
    <button class="btn btn-light btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#abrsm-modal">ABRSM</button>
    <button class="btn btn-light btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#rcm-modal">RCM</button>
</div>
@component('admin.components.filters.form', ['id' => 'piece-directory-filters', 'label' => 'Piece filters', 'resetUrl' => request()->url()])
    @include('components.forms.checkbox', ['id' => 'piece-filter-creator', 'name' => 'creator_id', 'value' => auth('admin')->id(), 'label' => 'Created by me', 'checked' => request('creator_id') == auth('admin')->id(), 'submitOnChange' => true])
    @foreach(['itunes' => 'Missing iTunes links', 'videos' => 'Missing videos', 'audio_path' => 'Missing audio', 'score_path' => 'Missing score', 'is_free' => 'Public domain'] as $name => $label)
        @include('components.forms.checkbox', ['id' => 'piece-filter-'.$name, 'name' => $name, 'value' => $name === 'is_free' ? 'false' : 'missing', 'label' => $label, 'checked' => (bool) request($name), 'submitOnChange' => true])
    @endforeach
@endcomponent
