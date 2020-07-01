<div class="col-lg-6 col-md-6 col-12 mb-4">
	<div class="row">
		<div class="col-lg-8 col-md-8 col-12">
			<h5>You're about to purchase...</h5>
			<div class="position-relative">
				<img src="{{$ebook->cover_image()}}" class="w-100">
				@include('shop.ebooks.components.discount-tag', ['position' => 'left'])
			</div>
		</div>
		<div class="col-12">
			<h4 class=" clamp-2"><strong>{{$ebook->title}}</strong></h4>
			<p>{{$ebook->subtitle}}</p>
			<div class="d-flex border-y p-2 my-2">
				<div class="mr-2">
					<h4 class="m-0">Total in USD:</h4>
				</div>
				<div>
					@include('shop.ebooks.components.price')
				</div>
			</div>
		</div>
	</div>
</div>