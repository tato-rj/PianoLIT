<div class="col-12">
	<div class="alert alert-red">
		@icon('ban', ['mr' => 2]) {{$user->first_name}}'s membership expired on <strong>{{$user->membership->source->renews_at->toFormattedDateString()}}</strong> and it was last validated on {{$user->membership->source->validated_at->toDayDateTimeString()}}.
	</div>
</div>

@include('admin.pages.users.show.membership.info')