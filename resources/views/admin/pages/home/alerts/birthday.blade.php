<div class="alert alert-warning alert-dismissible fade show" role="alert">
	<i class="fas fa-birthday-cake mr-2"></i> {{$composer->name}} was born on this day {{now()->diffInYears($composer->date_of_birth)}} years ago on {{$composer->date_of_birth->toFormattedDateString()}}.
	<button type="button" class="close" data-dismiss="alert" aria-label="Close">
		<span aria-hidden="true">&times;</span>
	</button>
</div>