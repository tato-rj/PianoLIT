<div class="bg-light rounded px-4 py-3">
  <label class="text-muted">Create a new playlist</label>
  <form method="POST" action="{{route('admin.playlists.store')}}" enctype="multipart/form-data">
    @csrf
    <div class="form-row mb-2">
      <div class="col">
        <input type="text" name="name" placeholder="Name" class="form-control mb-2" value="{{old('name')}}" required>
        <input type="text" name="subtitle" maxlength="72" placeholder="Subtitle" class="form-control mb-2" value="{{old('subtitle')}}" required>
        <input type="text" name="featured" maxlength="16" placeholder="Featured tag (optional)" class="form-control mb-2" value="{{old('featured')}}">
        <div class="custom-file mb-2">
          <input type="file" class="custom-file-input {{$errors->has('cover') ? 'is-invalid' : ''}}" name="cover" id="customFile">
          <label class="custom-file-label truncate" for="customFile">Cover image</label>
        </div>
        <div class="custom-control custom-checkbox">
          <input type="checkbox" name="group" value="journey" class="custom-control-input" id="is_journey" {{old('group') == 'journey' ? 'checked' : null}}>
          <label class="custom-control-label" for="is_journey">Is this playlist part of the "Follow a path"?</label>
        </div>
      </div>
      <div class="col">
        <textarea name="description" placeholder="Description" class="form-control h-100" maxlength="255" required>{{old('description')}}</textarea>
      </div>
    </div>
    <div class="w-100 text-right">
      <button type="submit" class="btn btn-default">Create</button>
    </div>
  </form>
  @include('admin.components.feedback', ['field' => 'name'])
</div>