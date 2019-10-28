<div class="col-lg-4 col-md-6 col-12 p-3">
	<div class="card border-0 shadow-light w-100 t-2">
		<a class="link-none" href="{{route('posts.show', $post->slug)}}">
			<div class="card-img-top rounded-top bg-align-center position-relative" style="background-image: url({{$post->cover_image()}}); height: 200px">
				
				@include('components.cards.new', ['is_new' => $post->is_new])

				<div class="card-overlay h-100 t-2" style="opacity: 0">
					<div class="text-white overlay-blue d-flex flex-center rounded-top"><i class="fas fa-eye fa-3x"></i></div>
				</div>
			</div>
			<div class="card-body rounded-bottom">
				<p class="text-muted mb-1"><small>{{$post->created_at->toFormattedDateString()}} &bull; {{$post->reading_time}} min read</small></p>
				<h5 class="card-title">{{$post->title}}</h5>
				<p class="card-text">{{$post->description}}</p>
			</div>
		</a>
	</div>
</div>