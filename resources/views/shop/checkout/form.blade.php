<div class="col-lg-6 col-md-6 col-12 mb-4">
	<h4 class="mb-3">@fa(['icon' => 'lock', 'size' => 'sm'])Payment details</h4>
	<p>All transactions are secure and encrypted</p>
	<form action="{{$action}}" method="POST" id="payment-form" class="mb-4" data-key="{{config('services.stripe.key')}}">
		@csrf
		<div class="mb-4">
			@empty($hasCard)
			<div class="form-group">
				<div id="card-element" class="form-control"></div>
				<div id="card-errors" role="alert" class="invalid-feedback d-block"></div>
			</div>
			@else
			<div class="form-group">
				<div class="custom-control custom-radio mb-2">
					<input checked type="radio" id="current-card" name="payment-method" class="custom-control-input">
					<label class="custom-control-label" for="current-card">Use {{auth()->user()->customer->card_brand}} {{auth()->user()->customer->card_last_four}}</label>
				</div>
				<div class="custom-control custom-radio mb-2">
					<input type="radio" id="new-card" name="payment-method" class="custom-control-input">
					<label class="custom-control-label" for="new-card">Use a different payment method</label>
				</div>
			</div>
			<div id="card-fields" class="form-group" style="display: none;">
				<div id="card-element" class="form-control"></div>
				<div id="card-errors" role="alert" class="invalid-feedback d-block"></div>
			</div>
			@endempty

			<div class="form-group">
				<input type="text" name="coupon" placeholder="Have a coupon code?" class="form-control">
				<div id="coupon-feedback" data-url="{{route('shop.validate-coupon')}}" class="d-block"></div>
			</div>

			<div><small>By purchasing I agree to the <a href="{{route('terms')}}" target="_blank" class="link-blue">Terms of Service</a>.</small></div>
		</div>
		<button id="card-button" class="btn btn-block btn-default">@fa(['icon' => 'lock']){{$label}}</button>
	</form>
	<div class="text-muted mb-1"><small>{{$comments}}</small></div>
	<div class="text-muted"><small>All prices are listed in US Dollars.</small></div>
</div>