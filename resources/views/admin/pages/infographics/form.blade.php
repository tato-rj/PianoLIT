@php
    $failed = $errors->getBag('infographic')->any();
    $selectedTopics = $failed ? old('topics', []) : ($infograph ? $infograph->topics->pluck('id')->all() : []);
    $selectedTopics = is_array($selectedTopics) ? array_filter($selectedTopics, 'is_scalar') : [];
@endphp
<form id="{{ $infograph ? 'edit-form' : 'create-infographic' }}" method="POST"
    action="{{ $infograph ? route('admin.infographs.update', ['infograph' => $infograph] + $returnFilters) : route('admin.infographs.store', $returnFilters) }}"
    enctype="multipart/form-data">
    @csrf
    @if($infograph) @method('PATCH') @endif
    <fieldset>
        <legend class="visually-hidden">{{ $infograph ? 'Infographic editor' : 'New infographic' }}</legend>
        <div class="admin-editor-grid">
            <div class="admin-editor-stack">
                @component('admin.components.editor.section', [
                    'id' => 'infographic-details', 'title' => 'Details',
                    'description' => $infograph ? number_format($infograph->downloads).' '.str_plural('download', $infograph->downloads).' · Score '.number_format($infograph->score) : null,
                ])
                    @input(['bag' => 'infographic', 'id' => 'infographic-name', 'name' => 'name', 'label' => 'Name', 'value' => $infograph ? $infograph->name : '', 'limit' => 120, 'useOldInput' => $failed])
                    @textarea(['bag' => 'infographic', 'id' => 'infographic-description', 'name' => 'description', 'label' => 'Description', 'value' => $infograph ? $infograph->description : '', 'oldInputKey' => $failed ? 'description' : '__unused_description', 'limit' => 238, 'rows' => 5])
                @endcomponent
            </div>
            <div class="admin-editor-stack">
                @include('admin.pages.infographics.fields.image')
                @include('admin.pages.infographics.fields.topics')
            </div>
        </div>
    </fieldset>
    @component('admin.components.table-save-actions', ['status' => $infograph ? 'Changes take effect when you save.' : 'Not saved yet'])
        <button type="submit" class="btn btn-primary">@icon($infograph ? 'check' : 'plus'){{ $infograph ? 'Save changes' : 'Create infographic' }}</button>
    @endcomponent
</form>
