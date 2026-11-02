@component('admin.components.editor.section', ['id' => 'piece-details', 'title' => 'Piece details'])
    <div class="row g-3">
        @input(['bag' => 'default', 'id' => 'piece-name', 'name' => 'name', 'label' => 'Name', 'value' => $piece->name, 'limit' => 255, 'grid' => 'col-sm-8', 'placeholder' => 'Piece name'])
        @input(['bag' => 'default', 'id' => 'piece-nickname', 'name' => 'nickname', 'label' => 'Nickname', 'value' => $piece->nickname, 'required' => false, 'grid' => 'col-sm-4'])
        @select(['bag' => 'default', 'id' => 'piece-composer', 'name' => 'composer_id', 'label' => 'Composer', 'choices' => $composers->pluck('short_name', 'id')->all(), 'value' => $piece->composer_id, 'placeholder' => 'Choose a composer', 'grid' => 'col-sm-8'])
        <div class="col-sm-4">
            <span class="small text-muted d-block mb-2">Attribution</span>
            <div class="d-flex align-items-center gap-2">
                @toggle(['toggle' => (bool) old('is_attributed_to', $hasOldInput ? false : $piece->is_attributed_to), 'name' => 'is_attributed_to', 'autoToggle' => false, 'ariaLabel' => 'Attributed to this composer'])
                <span class="small">Attributed to</span>
            </div>
        </div>
        @input(['bag' => 'default', 'id' => 'piece-collection', 'name' => 'collection_name', 'label' => 'Collection name', 'value' => $piece->collection_name, 'required' => false, 'grid' => 'col-sm-6'])
        @input(['bag' => 'default', 'id' => 'piece-collection-number', 'name' => 'collection_number', 'label' => 'Collection number', 'value' => $piece->collection_number, 'required' => false, 'grid' => 'col-6 col-sm-3', 'placeholder' => 'No.'])
        @input(['bag' => 'default', 'id' => 'piece-movement', 'name' => 'movement_number', 'label' => 'Movement number', 'value' => $piece->movement_number, 'required' => false, 'grid' => 'col-6 col-sm-3', 'placeholder' => 'Movement'])
    </div>
@endcomponent
