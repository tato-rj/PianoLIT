<div class="row">
	<div class="col-lg-6 col-md-6 col-12">
		<div class="position-relative">
			<img src="{{$ebook->cover_image()}}" class="w-100">
			@include('shop.ebooks.components.discount-tag', ['position' => 'left'])
		</div>
	</div>
	<div class="col-lg-6 col-md-6 col-12 d-flex align-items-center">
		<div>
			@topics(['topics' => \App\Blog\Post::first()->topics, 'route' => 'ebooks.topic'])
			<div>
				<h4 class=" clamp-2"><strong>{{$ebook->title}}</strong></h4>
				<p>{{$ebook->subtitle}}</p>
				@include('shop.ebooks.components.price')
			</div>
			
			<div>
				@include('shop.ebooks.components.action')
				<a href="{{route('ebooks.show', $ebook)}}" class="btn btn-sm btn-wide btn-outline-secondary mb-2">@fa(['icon' => 'info-circle'])More details</a>
			</div>
		</div>
	</div>
</div>