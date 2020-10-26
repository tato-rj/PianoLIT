<div class="col-lg-3 col-md-6 col-12 p-2 grid-item">
	<div class="card w-100 border-0 shadow-light rounded t-2">
		<a class="link-none" href="{{route('posts.show', $item->slug)}}">
			<div class="card-img-top rounded-top bg-align-center position-relative" style="background-image: url({{$item->cover_image()}}); height: 100px">
				<div class="card-overlay h-100 t-2" style="opacity: 0">
					<div class="text-white overlay-blue d-flex flex-center rounded-top"><i class="fas fa-eye fa-3x"></i></div>
				</div>
			</div>
			<div class="card-body">
				<h6 class="card-title mb-1">{{$item->title}}</h6>
				<p class="text-muted m-0"><small>{{$item->created_at->toFormattedDateString()}} &bull; {{$item->reading_time}} min read</small></p>
			</div>
		</a>
	</div>
</div>