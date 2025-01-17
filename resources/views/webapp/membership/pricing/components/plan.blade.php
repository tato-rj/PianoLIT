@php($isYearly = $plan->name == 'yearly')
<div class="col-lg-6 col-md-6 col-11 mx-auto p-2">
	<div class="p-5 position-relative rounded border h-100 d-flex flex-column justify-content-between">
		@if($isYearly)
		<span class="px-3 py-1 bg-intermediate-raw position-absolute best-value"><strong>BEST VALUE</strong></span>
		@endif

		<div>
			<h5 class="text-nowrap">Premium {{ucfirst($plan->name)}}</h5>
			<div class="d-flex align-items-baseline mb-3">
				<h1 class="mb-0 mr-2">
					${{$isYearly ? $plan->formattedMonthlyPrice() : $plan->formattedPrice()}}
				</h1>
				<div class="text-muted" style="font-size: 95%">monthly</div>
			</div>
			@if($isYearly)
			<p class="text-muted">${{$plan->formattedPrice()}} billed annually</p>
			@endif
		</div>
		<div>
			<p>{{$plan->description}}</p>
			<a href="{{route('webapp.membership.checkout', $plan->name)}}" class="btn btn-default btn-block">Start 7-day FREE trial</a>
		</div>
	</div>
</div>