<div class="col-lg-6 col-md-6 col-12 mb-4">
	<h4 class="mb-3">@fa(['icon' => 'lock', 'size' => 'sm'])Payment details</h4>
	<p>All transactions are secure and encrypted</p>
	<form action="{{route('webapp.membership.purchase', $plan)}}" method="POST" id="payment-form" class="mb-4">
		@csrf
		<div class="mb-4">
			<div class="form-group">
				<div id="card-element" class="form-control"></div>
				<div id="card-errors" role="alert" class="invalid-feedback d-block"></div>
			</div>

			<div class="form-group">
				<input type="text" name="coupon" placeholder="Have a coupon code?" class="form-control">
				<div id="coupon-feedback" class="d-block"></div>
			</div>

			<div><small>By purchasing I agree to the <a href="" class="link-blue">Terms of Service</a>.</small></div>
		</div>
		<button id="card-button" class="btn btn-block btn-default">@fa(['icon' => 'lock'])Subscribe now for ${{$plan->formattedPrice()}}</button>
	</form>
	<div class="text-muted mb-1"><small>Your free trial will start today and end on {{now()->addDays(7)->toFormattedDateString()}}. Unless you cancel during this duration, you’ll be charged ${{$plan->formattedPrice()}} after {{$plan->trial_period_days}} days. Afterwards your subscription will renew automatically every {{$plan->interval}}, but you can cancel anytime.</small></div>
	<div class="text-muted"><small>All prices are listed in US Dollars</small></div>
</div>