@component('components.modal', ['id' => 'add-modal', 'header' => 'New topic'])
@slot('body')
  <form method="POST" action="{{route('admin.topics.store')}}">
    @csrf
    @input(['bag' => 'default', 'name' => 'name', 'placeholder' => 'Create a new topic here'])    
    @submit(['label' => 'Create playlist', 'block' => true])
  </form>
@endslot
@endcomponent