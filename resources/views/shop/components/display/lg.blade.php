<div class="row mb-5">
	<div class="col-lg-6 col-md-6 col-12">
		@include('shop.components.cover', ['maxWidth' => '280px'])
	</div>
	<div class="col-lg-6 col-md-6 col-12 d-flex align-items-center">
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
</div>