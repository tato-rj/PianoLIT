<div class="text-danger" title="{{$user->first_name}}'s membership has been inactive since {{$user->membership->source->renews_at->toFormattedDateString()}}">
	<i class="fas fa-ban"></i>
</div>