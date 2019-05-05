@if($user->getStatus() == 'trial')
<span class="text-warning">Trial ends in {{$user->trial_ends_at->diffForHumans()}} ({{$user->trial_ends_at->toFormattedDateString()}})</span>
@elseif($user->getStatus() == 'expired')
<span class="text-muted">Trial expired</span>
@elseif($user->getStatus() == 'active')
<span class="text-success">Active</span>
@elseif($user->getStatus() == 'inactive')
<span class="text-danger">Membership expired (validated {{$user->membership->validated_at->diffForHumans()}})</span>
@endif