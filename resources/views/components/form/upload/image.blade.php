<div class="form-group mb-0">
  <div id="upload-box">
    <input type="file" data-target="#image" id="image-input" name="{{$name}}" style="display:none;" />

    <div class="position-relative image-container">
      @if($empty)
      <div class="bg-light px-2 text-muted border-left border-top border-right rounded-top">
        <small><strong><i class="fas fa-image mr-2"></i>Cover image</strong></small>
      </div>
      @endif

      <img class="w-100 border rounded-bottom" id="image" src="{{$image}}">
      
      <div class="controls d-flex justify-content-between mt-2">
        <button type="button" id="upload-button" class="btn btn-sm btn-warning">
          <i class="fas fa-folder-open mr-2"></i>{{$empty ? 'Choose image' : 'Change image'}}
        </button>

        <button type="button" id="confirm-button" style="display: none;" class="btn btn-sm btn-success">
          <i class="fas fa-check-circle mr-2"></i>Confirm
        </button>

        <button type="button" id="cancel-button" style="display: none;" class="btn btn-sm btn-danger">
          <i class="fas fa-times-circle mr-2"></i>Cancel
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