<div class="col-12 p-2 mb-3">
	<div class="card border-0 rounded">
		<a class="link-none" href="{{route('posts.show', $post->slug)}}">
		  <div class="row no-gutters">
		    <div class="col-lg-3">
				<div class="card-img-top bg-align-center h-100" style="background-image: url({{$post->cover_image()}});"></div>
		    </div>
		    <div class="col-lg-9">
		      <div class="card-body">
				<p class="text-muted mb-1"><small>{{$post->created_at->toFormattedDateString()}} &bull; {{$post->reading_time}} min read</small></p>
				<h5 class="card-title">{{$post->title}}</h5>
				<p class="card-text">{{$post->description}}</p>
		      </div>
		    </div>
		  </div>
		</a>
	</div>
</div>