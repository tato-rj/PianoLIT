<div class="position-fixed w-100 h-100vh fixed-panel" id="notifications-panel" style="z-index: 100000000; top: 0; left: 0; display: none;">
	@include('components.panel.overlay')

	<div class="bg-white position-absolute h-100 panel-content px-4 py-3" style="right: -100%; transition: .4s">
		<div class="panel-header d-flex d-apart mb-4">
			<h5 class="m-0">Notifications</h5>
			<button type="button" style="margin-bottom: 1px" class="close" data-dismiss="fixed-panel" aria-label="Close">
				<span class="mb-1" aria-hidden="true">&times;</span>
			</button>
		</div>
		<div>
			<div class="list-group">
				@include('components.panel.item')
				@include('components.panel.item')
			</div>
			{{-- You have no new notifications --}}
		</div>
	</div>
</div>