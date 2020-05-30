<div class="border-top border-bottom pt-4 pb-2 my-4">
	<a href="{{route('webapp.blog.show', $post)}}" class="link-none">
		<div class="row">
			<div class="col-lg-3 col-md-4 col-12">
				<div class="bg-align-center position-relative h-100 rounded" style="background-image: url({{$post->cover_image()}}); min-height: 140px"></div>
			</div>
			<div class="col-lg-9 col-md-8 col-12 py-2">
				<div class="text-blue mb-1 text-uppercase"><small><strong>{{$post->topics()->exists() ? $post->topics->first()->name : 'TO READ'}}</strong></small></div>
				<h4 class="mb-1 clamp-2"><strong>{{$post->title}}</strong></h4>
				<div class="hide-on-sm" style="line-height: 1"><small>{{$post->description}}</small></div>
			</div>
		</div>
	</a>
</div>