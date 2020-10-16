<div class="bg-light rounded p-5">
	<div class="row">
		<div class="col-lg-8 col-md-8 col-12 d-flex align-items-center">
			<div>
				<h4 class="mb-4 clamp-2"><strong>{{$product->title}}</strong></h4>
				<p>{{$product->subtitle}}</p>
				<div class="mb-2">
					@include('shop.components.price')
				</div>
			</div>
		</div>
		<div class="col-lg-4 col-md-4 col-12">
			@include('shop.components.highlights')
				<div>
					@include('shop.components.action')
				</div>	
		</div>
	</div>
</div>