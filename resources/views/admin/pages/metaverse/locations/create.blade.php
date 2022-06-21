@component('components.modal', ['id' => 'add-modal', 'header' => 'New location'])
@slot('body')
<form method="POST" action="{{route('admin.metaverse.locations.store')}}">
  @csrf
  @input(['bag' => 'default', 'name' => 'name', 'placeholder' => 'Name', 'limit' => 120])
  @input(['bag' => 'default', 'name' => 'url', 'placeholder' => 'URL', 'limit' => 255])
  @input(['bag' => 'default', 'name' => 'venue', 'placeholder' => 'Venue', 'limit' => 120])

  @submit(['label' => 'Create location', 'block' => true])
</form>
@endslot
@endcomponent