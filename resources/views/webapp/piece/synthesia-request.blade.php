@component('components.dialogs.modal', ['id' => 'synthesia-request-modal', 'centered' => true, 'options' => ['size' => 'sm'], 'descriptionId' => 'synthesia-request-description'])
@slot('header')
Request Synthesia?
@endslot

@slot('body')
<form id="synthesia-request-form" method="POST" action="{{route('webapp.users.synthesia-requests.store', $piece)}}">
	@csrf
	<p class="text-muted mb-3" id="synthesia-request-description">Ready in a few days.</p>

	<div class="d-flex justify-content-end gap-2">
		<button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-activity-label="Canceled Synthesia request">Cancel</button>
		<button type="submit" class="btn btn-primary">Send request</button>
	</div>
</form>
@endslot

@endcomponent
