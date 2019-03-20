<div class="d-flex justify-content-between mt-4 mb-5 mx-3">
  <div class="d-flex">
    <div class="mr-5">
      <img src="https://api.adorable.io/avatars/236/{{$user->email}}" class="rounded-circle" style="width: 160px">
    </div>

    <div class="mr-5">
      <div>
        <label class="text-brand m-0"><small>Name</small></label>
        <p>{{$user->full_name}}</p>
      </div>
      <div>
        <label class="text-brand m-0"><small>E-mail</small></label>
        <p>{{$user->email}}</p>
      </div>
    </div>
    
    <div class="mr-5">
      <div>
        <label class="text-brand m-0"><small>Language</small></label>
        <p>{{Locale::getDisplayName($user->locale)}}</p>
      </div>
      <div>
        <label class="text-brand m-0"><small>Status</small></label>
        <p>
          @if($user->getStatus() == 'trial')
            <span class="text-warning">Trial ends in {{$user->trial_ends_at->diffForHumans()}} ({{$user->trial_ends_at->toFormattedDateString()}})</span>
          @elseif($user->getStatus() == 'expired')
            <span class="text-muted">Trial expired</span>
          @elseif($user->getStatus() == 'active')
            <span class="text-success">Active</span>
          @elseif($user->getStatus() == 'inactive')
            <span class="text-danger">Subscription expired (validated {{$user->subscription->validated_at->diffForHumans()}})</span>
          @endif
        </p>
      </div>
    </div>
  </div>
</div>
