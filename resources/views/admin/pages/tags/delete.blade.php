@component('components.modal', ['id' => 'delete-tag-modal', 'header' => 'Delete tag', 'centered' => true, 'descriptionId' => 'delete-tag-description'])
@slot('body')
    <p id="delete-tag-description">Delete <strong data-delete-tag-name></strong>? This removes the tag from <span data-delete-tag-pieces></span>. The pieces themselves are kept.</p>
    <p class="small text-danger">This action cannot be undone.</p>
    <form method="POST" id="delete-tag-form">
        @csrf
        @method('DELETE')
        <div class="d-flex justify-content-end flex-wrap gap-2 mt-4">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            @include('admin.components.table-action', ['tableAction' => ['kind' => 'delete', 'type' => 'submit', 'label' => 'Delete tag', 'iconOnly' => false, 'attributes' => ['disabled' => true]]])
        </div>
    </form>
@endslot
@endcomponent
