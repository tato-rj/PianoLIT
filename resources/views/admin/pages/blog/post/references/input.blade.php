<div class="d-flex align-items-start gap-2" data-reference-row data-reference-index="{{ $referenceIndex }}">
    <div class="flex-grow-1">
        @input(['bag' => 'default', 'id' => 'post-reference-'.$referenceIndex, 'name' => 'references['.$referenceIndex.']', 'errorKey' => 'references.'.$referenceIndex, 'useOldInput' => false, 'label' => 'Reference', 'value' => $referenceValue, 'required' => false, 'limit' => 2000, 'placeholder' => 'Source or citation', 'grid' => 'mb-0'])
    </div>
    <div class="pt-4">
        @include('admin.components.table-action', ['tableAction' => ['kind' => 'delete', 'size' => 'xs', 'label' => 'Remove reference', 'attributes' => ['data-remove-reference' => true]]])
    </div>
</div>
