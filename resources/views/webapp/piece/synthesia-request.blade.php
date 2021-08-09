@component('components.modal', ['id' => 'synthesia-request-modal'])
@slot('header')
Synthesia tutorial
@endslot

@slot('body')
<form id="synthesia-request-form" method="POST" action="{{route('webapp.users.tutorial-requests.store', $piece)}}">
	@csrf
	<div class="mb-4">
		<p>Send your request for a synthesia video?</p>
		<input name="video_types[]" type="checkbox" hidden value="Synthesia" checked>
		<p class="text-muted text-center m-0"><small>These are custom made videos and it will take just a few days to make.</small></p>
	</div>

	<div class="d-flex flex-center w-100">
		<button class="btn rounded-pill btn-wide mx-2 btn-sm btn-outline-secondary" data-dismiss="modal">Cancel</button>
		<button type="submit" class="btn rounded-pill btn-wide mx-2 btn-sm btn-default">Submit my request</button>
	</div>
</form>
@endslot

@endcomponent