@component('components.modal', ['id' => 'tag-modal', 'header' => 'Edit tag'])
@slot('body')
  <form method="POST" id="edit-tag" class="form-row">
    @csrf
    @method('PATCH')
    @input(['bag' => 'default', 'label' => 'Name', 'name' => 'name', 'id' => 'name', 'grid' => 'col'])
    @select(['bag' => 'default', 'label' => 'Type', 'name' => 'type', 'placeholder' => 'Type', 'optGroups' => \App\Tag::labels(), 'grid' => 'col'])
    @submit(['label' => 'Save changes', 'block' => true])
  </form>  
@endslot

@slot('footer')
<div class="d-flex d-apart w-100">
  <div>
    <form method="POST" id="delete-tag">
      @csrf
      @method('DELETE')
      <button type="submit" class="btn btn-link btn-sm text-danger no-underline p-0"><i class="fas fa-trash-alt mr-2"></i>Delete tag</button>
    </form>
  </div>
  <div class="text-muted mb-0"><i><small>Tag created by <strong><span id="creator"></span></strong></small></i></div>
</div>
@endslot
@endcomponent
