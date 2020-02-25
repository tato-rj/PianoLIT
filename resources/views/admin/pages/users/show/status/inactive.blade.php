<div class="col-12">
	<div class="alert alert-red">
		<i class="fas fa-ban mr-2"></i> {{$user->first_name}}'s membership expired on <strong>{{$user->membership->renews_at->toFormattedDateString()}}</strong> and it was last validated on {{$user->membership->validated_at->toDayDateTimeString()}}.
	</div>
</div>

@include('admin.pages.users.show.membership.info')