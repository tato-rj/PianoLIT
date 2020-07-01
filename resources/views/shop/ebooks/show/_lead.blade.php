	<div class="row mb-5 pb-5 border-bottom">
		<div class="col-lg-6 col-md-6 col-12 d-flex align-items-center">
			<div>
				@topics(['topics' => \App\Blog\Post::first()->topics, 'route' => 'ebooks.topic'])
				<div>
					<h4 class="mb-4 clamp-2"><strong>{{$ebook->title}}</strong></h4>
					<ul class="list-style-none p-0 mb-4">
						<li class="lead">@fa(['icon' => 'check', 'color' => 'green'])Instant Digital Download</li>
						<li class="lead">@fa(['icon' => 'check', 'color' => 'green'])Lifetime Access</li>
						<li class="lead">@fa(['icon' => 'check', 'color' => 'green'])Easy one click payment</li>
					</ul>
				</div>
				
				<div>
					@include('shop.ebooks.components.price')
					<div>
						@include('shop.ebooks.components.action')
					</div>
					<div>
						<button data-toggle="modal" data-target="#preview-ebook" class="btn btn-sm btn-wide btn-outline-secondary mb-2">@fa(['icon' => 'book-open'])See a preview</a>
					</div>
				</div>
			</div>
		</div>
		<div class="col-lg-6 col-md-6 col-12 position-relative">
			<img src="{{$ebook->cover_image()}}" class="w-100">
			@include('shop.ebooks.components.discount-tag', ['position' => 'right'])
		</div>
	</div>