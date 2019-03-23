<div class="modal fade" id="trial-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Are you sure?</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        @if($user->getStatus() == 'trial')
        This will extend {{$user->first_name}}'s trial period to <strong>{{$user->trial_ends_at->addWeek()->toFormattedDateString()}}</strong>. {{$user->first_name}} will receive an email confirming this update.
        @elseif($user->getStatus() == 'expired')
        This will restart {{$user->first_name}}'s trial period, setting it to expire on <strong>{{now()->addWeek()->toFormattedDateString()}}</strong>. {{$user->first_name}} will receive an email confirming this update.
        @endif
      </div>
      <div class="modal-footer">
        <form method="POST" action="{{route('admin.users.update-trial', $user->id)}}">
          @csrf
          @method('PATCH')
          <button type="submit" class="btn btn-sm btn-block btn-default">Yes, go ahead!</button>
        </form>
      </div>
    </div>
  </div>
</div>