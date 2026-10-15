<div class="tab-pane fade" id="update-card-tab">
	<div class="row">
		@if($source->isEnded() || in_array($source->status, ['canceled', 'incomplete_expired'], true))
            <div class="col-12"><p>Your subscription has ended. Your past invoices are available in the Invoices tab.</p></div>
            @elseif(auth()->user()->membership->source->isCanceled())
			<div class="col-12">
				<div class="alert alert-warning mb-4">
					@icon('triangle-alert')Your membership was canceled on {{auth()->user()->membership->source->canceled_at->toFormattedDateString()}}
				</div>

				<div class="mb-4 text-center">
					<h6>Changed your mind?</h6>
					<form method="POST" action="{{route('webapp.membership.resume')}}" disable-on-submit>
						@csrf
                        <input type="hidden" name="operation_id" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
						<button type="submit" class="btn btn btn-sm btn-warning">Yes, don't cancel anymore</button>
					</form>
				</div>

				@if(auth()->user()->membership->source->isOnGracePeriod())
				<p>Your current billing period ends on <strong>{{auth()->user()->membership->source->membership_ends_at->toFormattedDateString()}}</strong>. Until then, you will still have access to PianoLIT. If you have any questions, please email us at <a href="mailto:contact@pianolit.com" class="link-blue">contact@pianolit.com</a>.</p>
				@endif
			</div>
		@else
		  <div class="col-lg-4 col-md-4 col-sm-6 col-12 mb-4">
			  <div class="list-group" id="list-tab">
					<a class="list-group-item border-0 rounded mb-1 list-group-item-action text-muted active" data-bs-toggle="list" href="#list-card">
					@icon('credit-card')Payment method</a>
					<a class="list-group-item border-0 rounded mb-1 list-group-item-action text-muted" data-bs-toggle="list" href="#list-pause">
					@icon('receipt')Billing status</a>
					<a class="list-group-item border-0 rounded mb-1 list-group-item-action text-muted" data-bs-toggle="list" href="#list-cancel">
					@icon('circle-x')Cancel membership</a>
			  </div>
		  </div>
		  <div class="col-lg-8 col-md-8 col-sm-6 col-12">
			<div class="tab-content">
				@include('webapp.membership.edit.sections.billing.card')
				@include('webapp.membership.edit.sections.billing.pause')
				@include('webapp.membership.edit.sections.billing.cancel')
			</div>
		  </div>
		@endif
	</div>

</div>
