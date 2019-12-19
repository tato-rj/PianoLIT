<div class="col-lg-4 col-md-6 col-12">
	<div class="rounded p-3 border">
		<div class="mb-2">
			<h6 class="m-0">{{$policy->data['name']}} Piano Studio</h6>
			<p class="text-muted mb-1">{{$policy->data['start_year']}}/{{$policy->data['end_year']}}</p>
			<p class="text-muted m-0"><small>from <strong>{{$policy->created_at->toFormattedDateString()}}</strong></small></p>
		</div>
		<div class="border-bottom mb-3 pb-3">
		<a class="btn btn-teal btn-block text-left" href="{{route('users.studio-policies.show', $policy->id)}}">
			<i class="fas fa-file-download mr-2"></i>Download</a>
		</div>
		<a class="btn btn-outline-secondary btn-xs btn-block text-left" href="{{route('users.studio-policies.edit', $policy->id)}}">
			<i class="fas fa-edit mr-2"></i>Edit</a>
		<a class="btn btn-outline-danger btn-xs btn-block text-left delete" href="" data-url="{{route('users.studio-policies.destroy', $policy->id)}}" data-toggle="modal" data-target="#delete-modal">
			<i class="fas fa-trash-alt mr-2"></i>Delete</a>
	</div>
</div>