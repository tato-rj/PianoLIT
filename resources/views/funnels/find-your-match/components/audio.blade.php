<div class="container-fluid">
	<div class="carousel-answers row"> 
		@foreach($media as $filename => $info)
		@include('funnels.find-your-match.components.play')
		@include('funnels.find-your-match.components.play')
		@endforeach
	</div>
</div>