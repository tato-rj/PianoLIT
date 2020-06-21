<div class="row">
	<div class="col-lg-6 col-md-6 col-12">
		<img src="{{asset('images/ebook-template.png')}}" class="w-100">
	</div>
	<div class="col-lg-6 col-md-6 col-12 d-flex align-items-center">
		<div>
			@topics(['topics' => \App\Blog\Post::first()->topics, 'route' => 'ebooks.topic'])
			<div>
				<h4 class=" clamp-2"><strong>{{$ebook->title}}</strong></h4>
				<p>{{$ebook->subtitle}}</p>
			</div>
			
			<div>
				<a href="#" class="btn btn-sm btn-wide btn-primary mb-2">@fa(['icon' => 'shopping-cart'])Buy now for {{$ebook->price_to_humans}}</a>
				<a href="{{route('ebooks.show', $ebook)}}" class="btn btn-sm btn-wide btn-outline-secondary mb-2">@fa(['icon' => 'info-circle'])More details</a>
			</div>
		</div>
	</div>
</div>