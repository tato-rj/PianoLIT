<div class="form-group mb-3">
  <div id="upload-box">
    <input type="file" data-target="#image" id="image-input" name="{{$name}}" style="display:none;" />

    <div class="position-relative image-container">
      @if($empty)
      <div class="bg-light px-2 text-muted border-start border-top border-end rounded-top">
        <small><strong>@icon('image', ['mr' => 2])Cover image</strong></small>
      </div>
      @endif

      <img class="w-100 border rounded-bottom" id="image" src="{{$image}}">

      <div class="controls d-flex justify-content-between mt-2">
        <button type="button" id="upload-button" class="btn btn-sm btn-warning">
          @icon('folder-open', ['mr' => 2]){{$empty ? 'Choose image' : 'Change image'}}
        </button>

        <button type="button" id="confirm-button" style="display: none;" class="btn btn-sm btn-success">
          @icon('circle-check', ['mr' => 2])Confirm
        </button>

        <button type="button" id="cancel-button" style="display: none;" class="btn btn-sm btn-danger">
          @icon('circle-x', ['mr' => 2])Cancel
        </button>
      </div>
    </div>
  </div>

  @if ($errors->has($name))
  <div class="invalid-feedback">
    {{ $errors->first($name) }}
  </div>
  @endif
</div>