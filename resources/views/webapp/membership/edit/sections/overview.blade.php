<div class="tab-pane fade show active" id="overview-tab">
	<div class="row"> 
		<div class="col-lg-4 col-md-4 col-sm-6 col-12 mb-3">
			<p class="lead text-nowrap mb-1">Your plan</p>
			<h5 class="m-0">{{auth()->user()->membership->source->plan_name}} Plan</h5>
		</div>
		<div class="col-lg-4 col-md-4 col-sm-6 col-12 mb-3">
			<p class="lead" style="margin-bottom: .18rem">Status</p>
			<div>{!! auth()->user()->membership->source->badge() !!}</div>
		</div>
		<div class="col-lg-4 col-md-4 col-sm-6 col-12 mb-3">
			<p class="lead mb-1">Payment info</p>
			<div>{!! auth()->user()->membership->source->card() ?? 'No card on file' !!}</div>
		</div>
		<div class="col-lg-4 col-md-4 col-sm-6 col-12 mb-3">
			@if(auth()->user()->isAuthorized())
				@if(auth()->user()->membership->source->willRenew())
				<p class="lead mb-1">Renews on</p>
				<div>{{auth()->user()->membership->source->renews_at->toFormattedDateString()}}</div>
				@else
				<p class="lead mb-1">Canceled on</p>
				<div>{{auth()->user()->membership->source->canceled_at->toFormattedDateString()}}</div>
				@endif
			@elseif(auth()->user()->membership->source->isPaused())
				<p class="lead mb-1">Stopped on</p>
				<div>{{auth()->user()->membership->source->paused_at->toFormattedDateString()}}</div>
			@elseif(auth()->user()->membership->source->isOnGracePeriod())
				<p class="lead mb-1">Expires on</p>
				<div>{{auth()->user()->membership->source->membership_ends_at->toFormattedDateString()}}</div>
			@elseif(auth()->user()->membership->source->isEnded())
				<p class="lead mb-1">Ended on</p>
				<div>{{auth()->user()->membership->source->ended_at->toFormattedDateString()}}</div>
			@endif
		</div>
		<div class="col-lg-4 col-md-4 col-sm-6 col-12 mb-3">
			<p class="lead mb-1">Created on</p>
			<div>{{ auth()->user()->membership->source->created_at->toFormattedDateString() }}</div>
		</div>
		<div class="col-lg-4 col-md-4 col-sm-6 col-12 mb-3">
			<p class="lead mb-1">Last updated on</p>
			<div>{{ auth()->user()->membership->source->updated_at->toFormattedDateString() }}</div>
		</div>
	</div>
</div>