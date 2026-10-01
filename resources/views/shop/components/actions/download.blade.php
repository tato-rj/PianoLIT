@auth
	@if(auth()->user()->purchasesOf($product)->exists())
		<div class="mb-{{$mb ?? 2}} text-center">
			<a href="{{route('users.purchases')}}" class="btn d-block w-100 btn-primary">@icon('cloud-download')Download it again</a>
			<p class="text-muted m-0"><small>Downloaded on {{auth()->user()->purchasesOf($product)->first()->created_at->toFormattedDateString()}}</small></p>
		</div>
	@elseif($product->isFree() || auth()->user()->isEligibleForFreeMonthlyProduct())
		<form method="POST" action="{{$product->purchaseRoute()}}" class="d-inline">
			@csrf
			<button class="btn d-block w-100 btn-primary mb-2">@icon('cloud-download')Download now</button>
		</form>
	@else
	<a href="{{$product->checkoutRoute()}}" class="btn d-block w-100 btn-primary mb-2">@icon('shopping-cart')Buy now</a>
	@endif
@else
	<a href="{{$product->checkoutRoute()}}" class="btn d-block w-100 btn-primary mb-2">@icon('shopping-cart')Buy now</a>
@endauth
