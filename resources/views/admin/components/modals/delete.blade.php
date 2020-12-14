@component('components.modal', ['id' => 'delete-modal', 'header' => 'Delete'])
@slot('body')
  Are you sure you want to do this?
  <p class="text-danger"><small>This action cannot be undone</small></p>
  <form method="POST">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-sm btn-block btn-danger">Yes, I am sure</button>
  </form>
@endslot
@endcomponent