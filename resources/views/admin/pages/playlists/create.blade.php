@component('components.modal', ['id' => 'add-modal', 'header' => 'New playlist'])
@slot('body')
  <form method="POST" action="{{route('admin.playlists.store')}}" enctype="multipart/form-data">
    @csrf
      @input(['bag' => 'default', 'name' => 'name', 'placeholder' => 'Name'])
      @input(['bag' => 'default', 'name' => 'subtitle', 'placeholder' => 'Subtitle', 'limit' => 72])
      @input(['bag' => 'default', 'name' => 'featured', 'placeholder' => 'Featured tag (optional)', 'limit' => 16, 'required' => false])
      @file(['bag' => 'default', 'name' => 'cover', 'placeholder' => 'Cover image'])
      @textarea(['bag' => 'default', 'name' => 'description', 'placeholder' => 'Description', 'rows' => 3])
      @options(['bag' => 'default', 'required' => false, 'type' => 'checkbox', 'name' => 'is_journey', 'options' => ['Is this playlist part of the "Follow a path"?' => true]])

      @submit(['label' => 'Create playlist', 'block' => true])
  </form>
@endslot
@endcomponent