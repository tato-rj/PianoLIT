<div class="modal fade" id="password-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Modal title</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form method="POST" action="{{route('admin.editors.update', $editor->id)}}">
          @csrf
          @method('PATCH')
          <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">
            <input id="password" type="password" placeholder="Password" class="form-control" name="password" required>
            @include('admin.components.feedback', ['field' => 'password'])
          </div>
          <div class="form-group">
            <input id="password-confirm" type="password" placeholder="Confirm your password" class="form-control" name="password_confirmation" required>
          </div>
          <button type="submit" class="btn btn-sm pull-right btn-default">Update password</button>
        </form>
      </div>
    </div>
  </div>
</div>