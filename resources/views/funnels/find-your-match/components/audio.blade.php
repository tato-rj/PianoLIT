<div class="container-fluid">
	<div class="carousel-answers row"> 
		@foreach($pieces as $piece)
		@include('funnels.find-your-match.components.play')
		@endforeach
	</div>
</div>