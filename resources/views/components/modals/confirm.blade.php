<div class="modal fade" id="confirm-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Confirm</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        Are you sure you want to <span id="form-action"></span>?
        <p class="text-danger"><small>This action cannot be undone</small></p>
      </div>
      <div class="modal-footer">
        <form method="POST">
          @csrf
          <button type="submit" class="btn btn-sm btn-block btn-default">Yes, I am sure</button>
        </form>
      </div>
    </div>
  </div>
</div>