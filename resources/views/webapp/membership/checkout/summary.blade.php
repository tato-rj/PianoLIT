<div class="col-lg-6 col-md-6 col-12 mb-4">
	<h4 class="mb-3">Your order</h4>
	<div class="border rounded mb-3">
		<div>
			<div class="d-flex d-apart p-4">
				<h5 class="m-0">1 {{ucfirst($plan->long_name)}}</h5>
				<h5 class="m-0"><strong>${{$plan->formattedPrice()}}</strong></h5>
			</div>
			<div class="text-center p-1 border-top border-bottom">
				<p class="m-0"><small>Billed after free trial</small></p>
			</div>
		</div>
		<div class="bg-light p-4 rounded-bottom text-center">
				<p class="text-brand"><strong>You will start with a {{$plan->trial_period_days}}-day free trial</strong></p>
				<p class="m-0 text-muted">You can cancel at any time during your trial and you <u>won’t be charged</u></p>
		</div>
	</div>
	<div>
		<p class="m-0 text-muted"><small>Not what you expected?</small></p>
		<a href="{{route('webapp.membership.checkout', $plan->other())}}" class="link-blue">Switch to the {{$plan->other()->long_name}}</a>
	</div>
</div>