@if($full ?? true)
<div class="row mb-4 t-2" id="youtube-previews" style="opacity: 0">
	<div class="col-12">
		<p class="text-center mx-auto">Subscribe to our <a href="{{route('youtube')}}" rel="nofollow" target="_blank">Youtube Channel</a> and enjoy weekly videos of awesome piano pieces!</p>
	</div>

	<div class="col-lg-4 col-md-4 col-12 p-3">
		<iframe class="w-100" height="260" src="https://www.youtube.com/embed/4V5OoQ8OLjY" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
	</div>
	<div class="col-lg-4 col-md-4 col-12 p-3">
		<iframe class="w-100 youtube-video-1" height="260" src="" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
	</div>
	<div class="col-lg-4 col-md-4 col-12 p-3">
		<iframe class="w-100 youtube-video-2" height="260" src="" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
	</div>

	<div class="col-12 text-center">
		@button([
			'href' => route('youtube'),
			'external' => true,
			'label' => '<i class="fab fa-lg fa-youtube mr-3"></i>Visit our channel',
			'styles' => [
				'shadow' => true,
				'theme' => 'primary',
			]])
	</div>
</div>
@endif