<div class="d-flex align-items-center">
	<div class="me-2">
		<a href="{{route('admin.pieces.edit', $item->piece)}}" class="btn btn-sm btn-outline-secondary">View</a>
	</div>
	<div class="me-2">
		<button class="btn btn-sm btn-outline-secondary view-request-types" {{! $item->types ? 'disabled' : null}} data-types="{{json_encode(unserialize($item->types))}}">Details</button>
	</div>
	<div>
	    @if($item->isPublished())
	    <div class="text-success text-nowrap">@icon('circle-check', ['mr' => 1])Published</div>
	    @else
	    <a href="#" data-url="{{route('admin.tutorial-requests.publish', $item->id)}}" data-bs-toggle="modal" data-bs-target="#publish-tutorial" class="btn btn-sm btn-warning text-nowrap">@icon('hourglass', ['mr' => 1])Publish</a>
	    @endif
	</div>
	@if(! $item->isPublished())
	<div class="ms-2">
		<a href="#" data-url="{{route('admin.tutorial-requests.destroy', $item)}}" title="Delete" data-bs-toggle="modal" data-bs-target="#delete-modal" class="delete text-muted d-none d-sm-block">
			@icon('trash-2', ['mr' => 0, 'classes' => 'align-middle'])
		</a>
	</div>
	@endif
</div>