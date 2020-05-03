<section class="mb-5">
	<h4 class="text-center mb-5">What's in it for you</h4>
	<div class="row">
		@include('webapp.pricing.components.feature', [
			'icon' => 'magic',
			'title' => 'Unlimited access',
			'description' => 'Enjoy full access to every resource available in PianoLIT.'
		])

		@include('webapp.pricing.components.feature', [
			'icon' => 'book-reader',
			'title' => 'Stay inspired',
			'description' => 'Search based on mood, technique, level, periods and more.'
		])

		@include('webapp.pricing.components.feature', [
			'icon' => 'stream',
			'title' => 'Keep on discovering',
			'description' => 'Love a certain piece? PianoLIT will show you other pieces just like that.'
		])

		@include('webapp.pricing.components.feature', [
			'icon' => 'box-open',
			'title' => 'Simple to use',
			'description' => 'Watch videos, request tutorials and listen to separate hands recordings.'
		])
		
		@include('webapp.pricing.components.feature', [
			'icon' => 'heart',
			'title' => 'Save pieces you love',
			'description' => 'Quickly get the resources for your favorite pieces anytime, anywhere.'
		])

		@include('webapp.pricing.components.feature', [
			'icon' => 'directions',
			'title' => 'Follow a path',
			'description' => 'Find inspiration by discovering the new pieces with curated playlists.'
		])
	</div>
</section>