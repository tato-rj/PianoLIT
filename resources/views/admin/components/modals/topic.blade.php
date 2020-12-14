@component('components.modal', ['id' => 'topic-modal', 'header' => 'Edit topic'])
@slot('body')
<form method="POST" id="edit-topic">
  @csrf
  @method('PATCH')
  @input(['bag' => 'default', 'label' => 'Name', 'name' => 'name', 'id' => 'name'])
  @submit(['label' => 'Save changes', 'block' => true])
</form>  
@endslot

@slot('footer')
<div class="d-flex d-apart w-100">
  <div>
    <form method="POST" id="delete-topic">
      @csrf
      @method('DELETE')
      <button type="submit" class="btn btn-link btn-sm text-danger no-underline p-0"><i class="fas fa-trash-alt mr-2"></i>Delete topic</button>
    </form>
  </div>
  <div class="text-muted mb-0"><i><small>Topic created by <strong><span id="creator"></span></strong></small></i></div>
</div>
@endslot
@endcomponent