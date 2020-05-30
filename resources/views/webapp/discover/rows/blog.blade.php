<div class="mb-4 pt-3">
	<a href="{{route('webapp.blog.show', $post)}}" class="link-none">
		<div class="row">
			<div class="col-lg-3 col-md-4 col-12">
				<div class="bg-align-center position-relative h-100 rounded" style="background-image: url({{$post->cover_image()}}); min-height: 140px"></div>
			</div>
			<div class="col-lg-9 col-md-8 col-12 py-2">
				<div class="text-blue mb-1 text-uppercase"><small><strong>{{$post->topics->first()->name}}</strong></small></div>
				<h5 class="mb-1 clamp-3"><strong>{{$post->title}}</strong></h5>
				<div class="hide-on-sm" style="line-height: 1"><small>{{$post->description}}</small></div>
			</div>
		</div>
	</a>
</div>