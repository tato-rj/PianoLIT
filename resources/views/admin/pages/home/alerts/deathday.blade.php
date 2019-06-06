<div class="alert alert-secondary alert-dismissible fade show" role="alert">
	<i class="fas fa-skull mr-2"></i> {{$composer->name}} died on this day {{now()->diffInYears($composer->date_of_death)}} years ago on {{$composer->date_of_death->toFormattedDateString()}}.
	<button type="button" class="close" data-dismiss="alert" aria-label="Close">
		<span aria-hidden="true">&times;</span>
	</button>
</div>