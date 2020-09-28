@component('components.modal', ['id' => 'delete-folder-' . $folder->id, 'title' => 'Delete my folder'])
@slot('body')
  <div class="mb-3">
    <div>Are you sure you want to delete the folder <strong>{{$folder->name}}</strong>?</div>
    @if($folder->favorites()->exists())
    <div>You will also lose all the pieces within it.</div>
    @endif
    <div class="text-danger mt-1"><small>This action cannot be undone</small></div>
  </div>
  <div>
    <form method="POST" action="{{route('webapp.users.favorites.folders.delete', $folder)}}" disable-on-submit>
      @csrf
      @method('DELETE')
      <button type="submit" class="btn btn-sm btn-block btn-danger">Yes, I am sure</button>
    </form>
  </div>
@endslot
@endcomponent