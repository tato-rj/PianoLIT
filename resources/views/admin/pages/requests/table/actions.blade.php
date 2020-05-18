<div class="d-flex align-items-center">
	<div class="mr-2">
		<button class="btn btn-sm btn-outline-secondary view-request-types" {{! $item->types ? 'disabled' : null}} data-types="{{json_encode(unserialize($item->types))}}">Details</button>
	</div>
	<div>
	    @if($item->isPublished())
	    <div class="text-success text-nowrap"><i class="fas fa-check-circle mr-1"></i>Published</div>
	    @else
	    <a href="#" data-url="{{route('admin.tutorial-requests.publish', $item->id)}}" data-toggle="modal" data-target="#modal-publish-tutorial" class="btn btn-sm btn-warning text-nowrap"><i class="fas fa-hourglass-half mr-1"></i>Publish</a>
	    @endif
	</div>
</div>