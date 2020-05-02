<div class="col-12 p-3">
	<div class="p-3 alert alert-green" role="alert">
		<i class="fas fa-exclamation-triangle mr-2"></i>{{$user->first_name}} is on trial! The plan will start in {{$user->membership->source->renews_at->diffForHumans()}} on <strong>{{$user->membership->source->renews_at->toFormattedDateString()}}</strong>
	</div>
</div>

@if($user->membership()->exists())
@include('admin.pages.users.show.membership.info')
@endif