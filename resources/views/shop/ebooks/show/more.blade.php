<div class="row">
	<div class="col-12">
		<h5 class="mb-4">You might also be interested in</h5>
	</div>
	@foreach($ebook->similar() as $similar)
	<div class="col-lg-3 col-md-4 col-6">
		<a href="" class="link-none">
			<img src="{{asset('images/ebook-temp/cover.jpg')}}" style="width: 80%" class="mb-2 shadow-center rounded mx-auto d-block">
			<p class="clamp-2"><strong>{{$ebook->title}}</strong></p>
		</a>
	</div>
	@endforeach
</div>