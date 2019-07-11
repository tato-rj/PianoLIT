<div class="row mb-4">
	<div class="col-12">
		<p class="text-center mx-auto">Subscribe to our <a href="{{route('youtube')}}" target="_blank" class="link-blue">Youtube Channel</a> and enjoy daily videos of awesome piano pieces and tips!</p>
	</div>
	@foreach($videos as $video)
	<div class="col-lg-4 col-md-4 col-12 p-3">
		<iframe class="w-100" height="260" src="https://www.youtube.com/embed/{{$video}}" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
	</div>
	@endforeach
	<div class="col-12 text-center">
		<a href="{{route('youtube')}}" target="_blank" class="btn btn-primary btn-wide shadow show-overlay"><i class="fab fa-lg fa-youtube mr-3"></i>Visit our channel</a>
	</div>
</div>