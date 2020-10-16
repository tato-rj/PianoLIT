<div class="row pb-5 pt-3">
		<div class="col-lg-8 col-md-8 col-12 d-flex mb-3 align-items-center order-lg-1 order-md-1 order-2">
			<div>
				@topics(['topics' => $product->topics])
				<div>
					<h4 class="mb-4 clamp-2"><strong>{{$product->title}}</strong></h4>
					@include('shop.components.highlights')
				</div>
				
				<div>
					<div class="mb-2">
						@include('shop.components.price')
					</div>
					<div>
						@include('shop.components.action')
					</div>
					<div>
						@include('shop.components.preview.button')
					</div>
				</div>
			</div>
		</div>

		<div class="col-lg-4 col-md-4 col-10 mx-auto mb-3 order-lg-2 order-md-2 order-1">
			@include('shop.components.cover')
		</div>
</div>