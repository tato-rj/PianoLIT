<div class="row mb-5">
	<div class="col-lg-6 col-md-6 col-10 mx-auto d-flex align-items-center mb-3 order-lg-1 order-md-1 order-2">
		<div>
			@topics(['topics' => $product->topics])
			<div class="mb-2">
				<h4 class=" clamp-2"><strong>{{$product->title}}</strong></h4>
				<p>{{$product->subtitle}}</p>
				@include('shop.components.price')
			</div>
			
			<div>
				@include('shop.components.action')
				<a href="{{$product->showRoute()}}" class="btn btn-sm btn-wide btn-outline-secondary mb-2">@fa(['icon' => 'info-circle'])More details</a>
			</div>
		</div>
	</div>

	<div class="col-lg-6 col-md-6 col-10 mx-auto mb-3 order-lg-2 order-md-2 order-1">
		@include('shop.components.cover', ['maxWidth' => '320px'])
	</div>
</div>