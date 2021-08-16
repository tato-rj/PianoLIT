@component('components.modal', ['id' => 'tutorial-request-modal'])
@slot('header')
Request tutorial
@endslot

@slot('body')
<form id="tutorial-request-form" method="POST" action="{{route('webapp.users.tutorial-requests.store', $piece)}}">
	@csrf
	<div class="mb-4">
		<p>What type of videos are you looking for?</p>
		<div class="mb-3">
			<div class="custom-control custom-checkbox mb-2">
			  <input name="video_types[]" type="checkbox" class="custom-control-input" id="slow-request" value="Slow performance">
			  <label class="custom-control-label" for="slow-request">Slow performance</label>
			</div>
			<div class="custom-control custom-checkbox mb-2">
			  <input name="video_types[]" type="checkbox" class="custom-control-input" id="harmony-request" value="Harmonic analysis">
			  <label class="custom-control-label" for="harmony-request">Harmonic analysis</label>
			</div>
			<div class="custom-control custom-checkbox mb-2">
			  <input name="video_types[]" type="checkbox" class="custom-control-input" id="tips-request" value="Practicing tips">
			  <label class="custom-control-label" for="tips-request">Practicing tips</label>
			</div>
		</div>
		<div id="tutorial-alert" class="text-red text-center" style="display: none;"><small>Please select at least one type of video</small></div>
		<p class="text-muted m-0"><small>These are custom made videos and it will take just a few days to make.</small></p>
	</div>

	<div class="d-flex flex-center w-100">
		<button class="btn rounded-pill btn-wide mx-2 btn-sm btn-outline-secondary" data-dismiss="modal">Cancel</button>
		<button type="submit" class="btn rounded-pill btn-wide mx-2 btn-sm btn-default">Submit my request</button>
	</div>
</form>
@endslot

@endcomponent