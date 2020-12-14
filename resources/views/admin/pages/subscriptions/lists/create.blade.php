@component('components.modal', ['id' => 'add-modal', 'header' => 'New list'])
@slot('body')
<form method="POST" action="{{route('admin.subscriptions.lists.store')}}" disable-on-submit>
  @csrf
  @input(['bag' => 'default', 'name' => 'name', 'placeholder' => 'List name'])
  @textarea(['bag' => 'default', 'name' => 'description', 'placeholder' => 'Describe the list here', 'rows' => 3])
  @submit(['label' => 'Create list', 'block' => true])
</form>
@endslot
@endcomponent
