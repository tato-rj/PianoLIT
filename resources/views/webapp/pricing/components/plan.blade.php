<div class="col-lg-6 col-md-6 col-12 p-2">
	<div class="p-5 position-relative rounded border h-100 d-flex flex-column justify-content-between">
		@isset($bestvalue)
		<span class="px-3 py-1 bg-intermediate-raw position-absolute best-value"><strong>BEST VALUE</strong></span>
		@endif

		<div>
			<h5 class="text-nowrap">Premium {{$plan}}</h5>
			<div class="d-flex align-items-baseline mb-3">
				<h1 class="mb-0 mr-2"><span>$</span>{{$price[0]}}</h1><div class="text-muted" style="font-size: 95%">montlhy</div>
			</div>
			@if(count($price) == 2)
			<p class="text-muted">${{$price[1]}} billed annually</p>
			@endif
		</div>
		<div>
			<p>{{$description}}</p>
			<button class="btn btn-default btn-block">Start 7-day FREE trial</button>
		</div>
	</div>
</div>