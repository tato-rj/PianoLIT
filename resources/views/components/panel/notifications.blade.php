<div class="position-fixed w-100 h-100vh fixed-panel" id="notifications-panel" style="z-index: 100000000; top: 0; left: 0; display: none;">
	@include('components.panel.overlay')

	<div class="bg-white position-absolute h-100 panel-content" style="right: -100%; transition: .4s">
		<div class="panel-header px-4 py-3">
			<div class="d-flex d-apart">
				<h5 class="m-0">Notifications</h5>
				<button type="button" style="margin-bottom: 1px" class="close" data-dismiss="fixed-panel" aria-label="Close">
					<span class="mb-1" aria-hidden="true">&times;</span>
				</button>
			</div>
		</div>

		<div class="panel-body px-4 py-3">
			<div class="list-group">
				@include('components.panel.item')
				@include('components.panel.item')
			</div>
			{{-- You have no new notifications --}}
		</div>

		<div class="panel-footer bg-light px-4 py-3 position-absolute w-100" style="left: 0; bottom: 0;">
			<div class="text-center">
				<a href="#"><small>Mark all as read</small></a>
			</div>
		</div>
	</div>
</div>