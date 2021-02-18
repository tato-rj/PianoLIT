<div class="row pb-5 pt-3">
	<div class="col-12">
		@include('shop.components.loyalty.notification')
	</div>
	<div class="col-lg-8 col-md-8 col-12 mx-auto d-flex mb-3 align-items-center order-lg-1 order-md-1 order-2">
		<div>
			@topics(['model' => $product])
			<div>
				@include('shop.components.title')
				<p class="text-muted">{{$product->subtitle}}</p>
				@include('shop.components.highlights')
			</div>
			
			<div>
				<a href="#reviews" class="link-none">
					@include('shop.components.reviews.stars', ['complete' => true])
				</a>
				<div class="mb-2">
					@include('shop.components.price', ['free' => auth()->check() && auth()->user()->isEligibleForFreeMonthlyProduct()])
				</div>

				{{$product->actionButtons()}}

			</div>
		</div>
	</div>

	<div class="col-lg-4 col-md-4 col-10 mx-auto mb-3 order-lg-2 order-md-2 order-1">
		@include('shop.components.mockup')
	</div>
</div>