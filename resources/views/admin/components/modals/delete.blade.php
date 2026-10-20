@component('components.modal', ['id' => 'delete-modal', 'header' => 'Delete'])
@slot('body')
  Are you sure you want to do this?
  <p class="text-danger"><small>This action cannot be undone</small></p>
  <form method="POST">
    @csrf
    @method('DELETE')
    @include('admin.components.table-action', ['tableAction' => ['kind' => 'delete', 'type' => 'submit', 'label' => 'Yes, I am sure', 'iconOnly' => false, 'class' => 'd-block w-100']])
  </form>
@endslot
@endcomponent