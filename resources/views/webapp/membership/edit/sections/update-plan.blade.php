<div class="tab-pane fade" id="update-plan-tab">
	<div class="d-flex justify-content-center mb-4">
		@foreach($plans as $plan)
		<button class="btn-raw border rounded p-4 text-center m-2 position-relative
			{{auth()->user()->membership->source->plan == $plan->name ? 'selected-plan' : null}}" 
			{{auth()->user()->membership->source->isCanceled() ? 'disabled' : null}}
			data-name="{{$plan->name}}" name="plan" style="width: 50%; max-width: 160px">
			@if(auth()->user()->membership->source->plan == $plan->name)
			<div id="plan-check" class="absolute-top-right">@fa(['icon' => 'check-circle', 'color' => 'green', 'size' => 'lg', 'mr' => 0])</div>
			@endif
			<div class="badge badge-pill alert-grey mb-3"><strong>{{strtoupper($plan->name)}}</strong></div>
			<h2 class="text-brand">${{$plan->formattedPrice()}}</h2>
			<p class="text-muted m-0"><small>billed each {{$plan->interval}}</small></p>
		</button>
		@endforeach
	</div>
	<div class="text-center" id="update-plan-action">
		@if(auth()->user()->membership->source->isCanceled())
		<p class="text-muted">Your membership is <u>canceled</u> and you can not change your membership plan anymore.</p>
		@else
		<p class="text-muted">You are currently subscribed to the <strong>{{auth()->user()->membership->source->plan_name}} Plan</strong>. If you want to switch plans, just select your preferred option above.</p>
		<form method="POST" action="{{route('webapp.membership.update.plan')}}" style="display: none;" disable-on-submit>
			@csrf
			<p class="text-muted">The new plan will take effect at the end of the current billing period.</p>
			<input type="hidden" name="plan">
			<button type="submit" id="update-plan-button" class="btn btn-default btn-wide">Update my plan</button>
		</form>
		@endif
	</div>
</div>