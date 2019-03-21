<div class="modal fade" id="tag-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Edit tag</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form method="POST" id="edit-tag" class="form-row">
          @csrf
          @method('PATCH')
          <div class="col">
            <label for="exampleFormControlSelect1">Name</label>
            <input type="text" name="name" class="form-control" id="name">
          </div>
          <div class="col">
            <label>Type</label>
            <select class="form-control" name="type">
              <optgroup label="Search tags">
                <option value="mood">Mood</option>
                <option value="technique">Technique</option>      
                <option value="genre">Genre</option>
              </optgroup>
              <optgroup label="Core tags">
                <option value="level">Level</option>
                <option value="period">Period</option>
                <option value="length">Length</option>
              </optgroup>
            </select>
          </div>
          <div class="col-12 text-right mt-2">
            <button type="submit" class="btn btn-sm btn-default">Save changes</button>
          </div>
        </form>  
      </div>
      <div class="modal-footer justify-content-between border-0 pt-0">
        <div>
          <form method="POST" id="delete-tag" class="w-100">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-link btn-sm text-danger no-underline p-0"><i class="fas fa-trash-alt mr-2"></i>Delete tag</button>
          </form>
        </div>
        <p class="text-muted mb-0"><i><small>Tag created by <strong><span id="creator"></span></strong></small></i></p>
      </div>
    </div>
  </div>
</div>