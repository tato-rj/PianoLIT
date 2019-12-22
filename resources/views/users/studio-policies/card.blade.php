<div class="col-lg-4 col-md-6 col-12">
	<div class="rounded p-3 border">
		<div class="mb-2">
			<h6 class="m-0">{{$policy->get('nickname')}}</h6>
			<p class="text-muted mb-1">{{$policy->get('start_year')}}/{{$policy->get('end_year')}}</p>
			<p class="text-muted m-0"><small>from <strong>{{$policy->created_at->toFormattedDateString()}}</strong></small></p>
		</div>
		<div class="border-bottom mb-2 pb-3">
		<a class="btn btn-teal btn-block text-left" href="{{route('users.studio-policies.show', $policy->id)}}">
			<i class="fas fa-file-download mr-2"></i>Download</a>
		</div>
		<div class="d-flex">
			<div class="w-50 text-center">
				<a class="link-grey" title="Edit" href="{{route('users.studio-policies.edit', $policy->id)}}">
					<small class="d-block">EDIT</small>
					<i class="fas fa-lg fa-edit"></i>
				</a>
			</div>
			<div class="w-50 text-center">
				<a class="link-grey delete" title="Delete" href="" data-url="{{route('users.studio-policies.destroy', $policy->id)}}" data-toggle="modal" data-target="#delete-modal">
					<small class="d-block">DELETE</small>
					<i class="fas fa-lg fa-trash-alt"></i>
				</a>
			</div>
		</div>
	</div>
</div>