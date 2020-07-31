<div class="row">
	<div class="col-lg-6 col-md-6 col-12">
		<div class="position-relative">
			<img src="{{$product->cover_image()}}" class="w-100">
			@include('components.shop.discount-tag', ['position' => 'left'])
		</div>
	</div>
	<div class="col-lg-6 col-md-6 col-12 d-flex align-items-center">
		<div>
			@topics(['topics' => \App\Blog\Post::first()->topics, 'route' => 'ebooks.topic'])
			<div class="mb-2">
				<h4 class=" clamp-2"><strong>{{$product->title}}</strong></h4>
				<p>{{$product->subtitle}}</p>
				@include('components.shop.price')
			</div>
			
			<div>
				@include('components.shop.action')
				<a href="{{$product->showRoute()}}" class="btn btn-sm btn-wide btn-outline-secondary mb-2">@fa(['icon' => 'info-circle'])More details</a>
			</div>
		</div>
	</div>
</div>