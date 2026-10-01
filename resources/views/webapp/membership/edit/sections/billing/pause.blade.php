<div class="tab-pane fade" id="list-pause">
	<p>
		@if(auth()->user()->membership->source->isPaused())
		Your membership is currently <strong>paused</strong> and you are not being charged at this time. If you wish to go back to your active status, just hit resume and your membership will continue as before.
		@else
		This will pause your membership and <u>you will no longer be charged</u>. At anytime, just hit resume and your membership will continue as before.
		@endif
	</p>
	<form method="POST" action="{{route('webapp.membership.update.billing-status')}}" class="mb-1" id="update-collection-form" disable-on-submit>
		@csrf

		@if(auth()->user()->membership->source->isPaused())
		<button type="submit" class="btn d-block w-100 btn-green">@icon('circle-play')Resume my membership</button>
		@else
		<button type="submit" class="btn d-block w-100 btn-warning">@icon('circle-pause')Pause my membership</button>
		@endif
	</form>
</div>