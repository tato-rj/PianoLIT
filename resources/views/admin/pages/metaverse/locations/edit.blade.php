@component('components.modal', ['id' => 'edit-'.$item->id.'-modal', 'header' => 'Update location'])
@slot('body')
<form method="POST" action="{{route('admin.metaverse.locations.update', $item)}}">
  @csrf
  @method('PATCH')
  @input(['bag' => 'default', 'name' => 'name', 'placeholder' => 'Name', 'limit' => 120, 'value' => $item->name])
  @input(['bag' => 'default', 'name' => 'url', 'placeholder' => 'URL', 'limit' => 255, 'value' => $item->url])
  @input(['bag' => 'default', 'name' => 'venue', 'placeholder' => 'Venue', 'limit' => 120, 'value' => $item->venue])

  @submit(['label' => 'Update location', 'block' => true])
</form>
@endslot
@endcomponent