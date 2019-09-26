<div class="modal fade" id="event-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Edit event</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form method="POST" id="edit-event">
          @csrf
          @method('PATCH')
          <div class="form-row form-group">
            <div class="col">
              <input type="number" name="year" min="1600" max="{{now()->year}}" class="form-control" id="year">
            </div>
            <div class="col">
              <select id="type" name="type" class="form-control mr-2">
                <option selected disabled>Type</option>
                <option value="history">History</option>
                <option value="music">Music</option>
                <option value="premiere">Premiere</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <input type="text" placeholder="Url address" name="url" class="form-control" id="url">
          </div>
          <div class="form-group">
            <textarea name="event" class="form-control" id="event"></textarea>
          </div>
          <div class="text-right">
            <button type="submit" class="btn btn-sm btn-default">Save changes</button>
          </div>
        </form>  
      </div>
    </div>
  </div>
</div>