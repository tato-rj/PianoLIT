<div class="bg-light rounded px-4 py-3">
  <label class="text-muted">Create a new playlist</label>
  <form method="POST" action="{{route('admin.playlists.store')}}" enctype="multipart/form-data">
    @csrf
    <div class="form-row mb-2">
      <div class="col">
        <input type="text" name="name" placeholder="Name" class="form-control mb-2" value="{{old('name')}}" required>
        <input type="text" name="subtitle" maxlength="72" placeholder="Subtitle" class="form-control mb-2" value="{{old('subtitle')}}" required>
        <div class="form-row">
          <div class="col">
            <select name="group" class="form-control">
              <option selected disabled>Select the group</option>
              <option value="" {{ old('group') == '' ? 'selected' : ''}}>General</option>
              <option value="journey" {{ old('group') == 'journey' ? 'selected' : ''}}>Journey</option>
            </select>
          </div>
          <div class="col">
            <div class="custom-file">
              <input type="file" class="custom-file-input {{$errors->has('cover') ? 'is-invalid' : ''}}" name="cover" id="customFile">
              <label class="custom-file-label truncate" for="customFile">Cover image</label>
            </div>
          </div>
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