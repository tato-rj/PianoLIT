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
			@if(auth()->user()->hasNewNotifications())
			<div class="">
				<a href="{{route('admin.notifications.read', ['url' => url()->current()])}}"><small>Mark all as read</small></a>
			</div>
			@endif
		</div>

		<div class="panel-body px-4 py-3" style="overflow-y: auto; height: 85%;">
			<div class="list-group">
				@forelse(auth()->user()->unreadNotifications->groupBy('data.message') as $group)
					@include('components.panel.item', ['notifications' => $group])
				@empty
				<i class="text-muted">You have no new notifications</i>
				@endforelse
			</div>
		</div>
		<div class="panel-footer bg-light px-4 py-3 position-absolute w-100" style="left: 0; bottom: 0;">
			<div class="text-center">
				<a href="{{route('admin.notifications.index')}}"><small>See all</small></a>
			</div>
		</div>
	</div>
</div>