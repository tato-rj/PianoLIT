<section class="py-5 overflow-hidden mb-5">
	<div class="swiper-container">
		<div class="swiper-wrapper">
			@foreach($testimonials as $testimonial)
			<div class="swiper-slide px-4">
				<div class="w-100">
					<h4 class="mb-4 text-blue text-truncate w-100">{{$testimonial['title']}}</h4>
					<p class="">{{$testimonial['content']}}</p>
					<footer class="blockquote-footer">{{$testimonial['author']}}</footer>
				</div>
			</div>
			@endforeach
		</div>

		<div class="swiper-nav">	    
		    <div class="swiper-button-prev swiper-navigation-arrow position-relative m-0"></div>
			<div class="swiper-pagination"></div>
		    <div class="swiper-button-next swiper-navigation-arrow position-relative m-0"></div>
		</div>
	</div>
</section>