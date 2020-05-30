<div class="border-top border-bottom py-4 my-4">
	<a href="{{route('webapp.blog.show', $post)}}" class="link-none">
		<div class="row">
			<div class="col-lg-3 col-md-4 col-12 py-1">
				<div class="bg-align-center position-relative h-100 rounded" style="background-image: url({{$post->cover_image()}}); min-height: 140px"></div>
			</div>
			<div class="col-lg-9 col-md-8 col-12 py-1">
				<div class="text-blue mb-1 text-uppercase"><small><strong>{{$post->topics()->exists() ? $post->topics->first()->name : 'TO READ'}}</strong></small></div>
				<h4 class="mb-0 clamp-2"><strong>{{$post->title}}</strong></h4>
				<div class="hide-on-sm mt-2" style="line-height: 1.2; font-size: 92%">{{$post->description}}</div>
			</div>
		</div>
	</a>
</div>