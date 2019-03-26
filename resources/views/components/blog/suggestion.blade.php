<div class="col-lg-3 col-md-6 col-12 p-2">
	<div class="card w-100 hover-shadow t-2">
		<a class="link-none" href="{{route('posts.show', $suggestion->slug)}}">
			<div class="card-img-top bg-align-center" style="background-image: url({{$suggestion->cover_image()}}); height: 100px"></div>
			<div class="card-body">
				<p class="text-muted mb-1"><small>{{$suggestion->created_at->toFormattedDateString()}} &bull; {{$suggestion->reading_time}} min read</small></p>
				<h6 class="card-title m-0">{{$suggestion->title}}</h6>
			</div>
		</a>
	</div>
</div>