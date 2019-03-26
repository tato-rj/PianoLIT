<div class="col-lg-4 col-md-6 col-12 p-2">
	<div class="card w-100 hover-shadow t-2">
		<a class="link-none" href="{{route('posts.show', $post->slug)}}">
			<div class="card-img-top bg-align-center" style="background-image: url({{$post->cover_image()}}); height: 200px"></div>
			<div class="card-body">
				<p class="text-muted mb-1"><small>{{$post->created_at->toFormattedDateString()}} &bull; {{$post->reading_time}} min read</small></p>
				<h5 class="card-title text-truncate">{{$post->title}}</h5>
				<p class="card-text">{{$post->description}}</p>
			</div>
		</a>
	</div>
</div>