<div class="mb-6">
	<div class="row">
		<div class="col-lg-6 col-md-6 col-12">
			@include('shop.components.mockup', ['maxWidth' => '280px'])
		</div>
		<div class="px-5 py-4 bg-light rounded col-lg-6 col-md-6 col-12 d-flex flex-center">
			<div>
				<div class="mb-3">
					@include('shop.components.title')
					<p>{{$product->subtitle}}</p>
					@include('shop.components.price')
				</div>

				@include('shop.components.actions.download', ['mb' => 0])
			</div>
		</div>
	</div>
</div>