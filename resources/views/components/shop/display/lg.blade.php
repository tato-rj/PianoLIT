<div class="row mb-5">
	<div class="col-lg-6 col-md-6 col-12">
		@include('components.shop.cover')
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