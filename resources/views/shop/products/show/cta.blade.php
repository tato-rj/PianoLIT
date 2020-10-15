<div class="bg-light rounded row mb-5">
	<div class="col-lg-9 col-md-9 col-12 p-5 d-flex align-items-center">
		<div>
			<h4 class="mb-4 clamp-2"><strong>{{$product->title}}</strong></h4>
			@include('shop.components.highlights')
			<div class="mb-2">
				@include('shop.components.price')
			</div>
			<div>
				@include('shop.components.action')
			</div>	
		</div>
	</div>
	<div class="col-lg-3 col-md-3 col-12 py-5 pr-5">
		@include('shop.components.cover')
	</div>
</div>