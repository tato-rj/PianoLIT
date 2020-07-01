<div class="row">
	<div class="col-12 text-center">
		<h5 class="mb-4">You might also be interested in</h5>
	</div>
	@foreach($ebook->suggestions()->get() as $suggestion)
	<div class="col-lg-3 col-md-4 col-6">
		<a href="{{route('ebooks.show', $suggestion)}}" class="link-none text-center">
			<img src="{{storage($suggestion->shelf_cover_path)}}" style="width: 80%" class="mx-auto d-block">
			<h6 class="clamp-2" style="">{{$suggestion->title}}</h6>
		</a>
	</div>
	@endforeach
</div>