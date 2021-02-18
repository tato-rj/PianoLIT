@auth
	@if(! $product->isFree() && ! auth()->user()->purchasesOf($product)->exists())
		@if(auth()->user()->membership()->exists() && auth()->user()->membership->source->isOnTrial() && ! auth()->user()->membership->source->isCanceled())
			@include('shop.components.loyalty.trial')
		@elseif(auth()->user()->loyaltyDiscounts()->lastMonth()->exists())
			@include('shop.components.loyalty.countdown')
		@elseif(auth()->user()->isEligibleForFreeMonthlyProduct())
			@include('shop.components.loyalty.eligible')
		@else
			@include('shop.components.loyalty.default')
		@endif
	@endif
@else
	@include('shop.components.loyalty.default')
@endauth