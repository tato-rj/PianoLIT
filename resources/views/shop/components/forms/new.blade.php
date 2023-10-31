<form action="{{$action}}" method="POST" disable-on-submit id="payment-form" class="mb-4 payment-forms" data-key="{{(new \App\Billing\Sources\Concerns\StripeJurisdiction)->set()}}" 
	style="{{! empty($hide) ? 'display: none' : null}}">
	@csrf
	<div class="mb-3">
		<div class="form-group">
			<div id="card-element" class="form-control"></div>
			<div id="card-errors" role="alert" class="invalid-feedback d-block"></div>
		</div>

		@include('shop.components.forms.coupon')
		
		@if(! empty($saveCard))
		<div class="">
			<div class="custom-control custom-checkbox">
				<input type="checkbox" checked class="custom-control-input" name="save_card" id="save_card">
				<label class="custom-control-label" for="save_card">Save this card for future purchases</label>
			</div>
		</div>
		@endif

		<div><small>By purchasing I agree to the <a href="{{route('terms')}}" target="_blank" class="link-blue">Terms of Service</a>.</small></div>
	</div>
	<button id="card-button" class="btn btn-block btn-default">@fa(['icon' => 'lock']){{$label}}</button>
</form>
<div class="text-muted mb-1"><small>{{$comments}}</small></div>
<div class="text-muted"><small>All prices are listed in US Dollars.</small></div>