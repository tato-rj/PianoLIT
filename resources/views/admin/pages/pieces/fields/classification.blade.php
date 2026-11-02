@component('admin.components.editor.section', ['id' => 'piece-classification', 'title' => 'Catalogue & classification'])
    <div class="row g-3">
        @select(['bag' => 'default', 'id' => 'piece-catalogue', 'name' => 'catalogue_name', 'label' => 'Catalogue', 'choices' => ['' => 'None'] + array_combine(catalogues(), catalogues()), 'value' => $piece->catalogue_name, 'required' => false, 'grid' => 'col-sm-3'])
        @input(['bag' => 'default', 'id' => 'piece-catalogue-number', 'name' => 'catalogue_number', 'label' => 'Catalogue number', 'value' => $piece->catalogue_number, 'required' => false, 'grid' => 'col-sm-3', 'placeholder' => 'Number'])
        @select(['bag' => 'default', 'id' => 'piece-key', 'name' => 'key', 'label' => 'Key', 'optGroups' => ['Tonal' => keys(), 'Non-tonal' => ['Atonal', 'Modal', 'Serial', 'Chromatic', 'Experimental']], 'select' => $piece->key, 'placeholder' => 'Choose a key', 'grid' => 'col-sm-6'])
        @input(['bag' => 'default', 'id' => 'piece-composed', 'name' => 'composed_in', 'label' => 'Composed in', 'type' => 'number', 'min' => 1600, 'limit' => now()->year, 'value' => $piece->composed_in, 'required' => false, 'grid' => 'col-sm-6', 'placeholder' => 'Year'])
        @input(['bag' => 'default', 'id' => 'piece-published', 'name' => 'published_in', 'label' => 'Published in', 'type' => 'number', 'min' => 1600, 'limit' => now()->year, 'value' => $piece->published_in, 'required' => false, 'grid' => 'col-sm-6', 'placeholder' => 'Year'])
        @foreach(['period' => 'Period', 'length' => 'Length', 'level' => 'Level'] as $field => $label)
            @select(['bag' => 'default', 'id' => 'piece-'.$field, 'name' => $field.'[]', 'label' => $label, 'choices' => $classifications[$field], 'value' => old($field.'.0', optional($piece->$field)->id), 'placeholder' => 'Choose '.strtolower($label), 'grid' => 'col-sm-4'])
        @endforeach
    </div>
@endcomponent
