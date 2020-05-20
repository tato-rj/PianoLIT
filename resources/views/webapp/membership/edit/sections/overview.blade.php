<div class="tab-pane fade show active" id="overview-tab">
	<div class="row"> 
		<div class="col-lg-4 col-md-4 col-sm-6 col-12 mb-3">
			<div class="d-flex flex-column border rounded px-3 text-center">
				<div class="mb-2 mt-1 py-3 border-bottom flex-grow">
					<h6>{{auth()->user()->membership->source->plan_name}} Plan</h6>
					<h4 class="m-0">{!! auth()->user()->membership->source->badge() !!}</h4>
					@if(auth()->user()->membership->source->isOnGracePeriod())
					<small class="text-danger">expires on {{auth()->user()->membership->source->membership_ends_at->format('n/j')}}</small>
					@endif
				</div>
				<div class="pb-3">
					<div><small>Last updated on</small></div>
					<div>{{auth()->user()->membership->source->updated_at->toFormattedDateString()}}</div>
				</div>
			</div>
		</div>
		<div class="col-lg-8 col-md-8 col-sm-6 col-12 mb-3">
			<div class="row">
				<div class="col-lg-6 col-md-6 col-12 mb-3">
					<p class="lead" style="margin-bottom: .18rem">Next invoice</p>
					@if(auth()->user()->membership->source->willRenew())
					<div>{{auth()->user()->membership->source->renews_at->toFormattedDateString()}}</div>
					@else
					<div class="text-muted"><i>No upcoming payments</i></div>
					@endif
				</div>
				<div class="col-lg-6 col-md-6 col-12 mb-3">
					<p class="lead mb-1">Payment info</p>
					<div>{!! auth()->user()->membership->source->card() ?? 'No card on file' !!}</div>
				</div>
				<div class="col-lg-6 col-md-6 col-12 mb-3">
					<p class="lead mb-1">Created on</p>
					<div>{{ auth()->user()->membership->source->created_at->toFormattedDateString() }}</div>
				</div>
				<div class="col-lg-6 col-md-6 col-12 mb-3">
					<p class="lead mb-1">Last updated on</p>
					<div>{{ auth()->user()->membership->source->updated_at->toFormattedDateString() }}</div>
				</div>
			</div>
		</div>
	</div>
</div>