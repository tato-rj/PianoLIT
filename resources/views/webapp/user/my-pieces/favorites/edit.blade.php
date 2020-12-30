@component('components.modal', ['id' => 'edit-folder-' . $folder->id, 'title' => 'Edit my folder', 'header' => 'Update folder'])
@slot('body')
  <div>
    <form method="POST" action="{{route('webapp.users.favorites.folders.update', $folder)}}" disable-on-submit>
      @csrf
      @method('PATCH')
      @input(['bag' => 'default', 'label' => 'Folder name', 'value' => $folder->name, 'name' => 'name', 'placeholder' => 'Write the name here', 'limit' => 80])
      @textarea(['bag' => 'default', 'label' => 'Folder description (optional)', 'value' => $folder->description, 'name' => 'description', 'placeholder' => 'Write the description here', 'limit' => 120, 'required' => true])

      <button type="submit" class="btn btn-sm btn-block btn-primary">Save my changes</button>
    </form>
  </div>
@endslot
@endcomponent