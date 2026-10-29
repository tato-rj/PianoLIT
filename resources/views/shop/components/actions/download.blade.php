@auth
	@if(auth()->user()->purchasesOf($product)->exists())
		<div class="mb-{{$mb ?? 2}} text-center">
			<a href="{{route('users.purchases')}}" class="{{ $actionClasses ?? 'btn d-block w-100 btn-primary' }}">@icon('cloud-download')Download it again</a>
			<p class="text-muted m-0"><span class="{{ isset($actionClasses) ? '' : 'small' }}">Downloaded on {{auth()->user()->purchasesOf($product)->first()->created_at->toFormattedDateString()}}</span></p>
		</div>
	@elseif($product->isFree() || auth()->user()->isEligibleForFreeMonthlyProduct())
		<form method="POST" action="{{$product->purchaseRoute()}}" class="d-inline">
			@csrf
			<button class="{{ $actionClasses ?? 'btn d-block w-100 btn-primary' }} mb-2">@icon('cloud-download')Download now</button>
		</form>
	@else
	<a href="{{$product->checkoutRoute()}}" class="{{ $actionClasses ?? 'btn d-block w-100 btn-primary' }} mb-2" @if(isset($actionClasses) && app(\App\Services\Shop\CheckoutEnvironment::class)->available('digital')) data-checkout-open @endif>@icon('shopping-cart')Buy now</a>
	@endif
@else
	<a href="{{$product->checkoutRoute()}}" class="{{ $actionClasses ?? 'btn d-block w-100 btn-primary' }} mb-2" @if(isset($actionClasses) && app(\App\Services\Shop\CheckoutEnvironment::class)->available('digital')) data-checkout-open @endif>@icon('shopping-cart')Buy now</a>
@endauth
