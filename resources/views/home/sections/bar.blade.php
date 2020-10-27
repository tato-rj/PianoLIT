<div style="background: #0055fe; color: white">
	<section class="container mb-5">
		<div class="row align-items-center py-7">
			<div class="col-lg-6 col-md-6 col-sm-6 col-12">
				<h1 class="my-2">What is PianoLIT?</h1>
			</div>
			<div class="col-lg-6 col-md-6 col-sm-6 col-12">
				<p class="mb-4 lead" style="font-size: 110%">PianoLIT is the first search engine for pianists that helps you navigate the repertoire and find pieces that match your level, mood, and interest.</p>
	
				@button([
					'href' => route('webapp.discover'),
					'label' => 'Start FREE trial',
					'styles' => [
						'shadow' => true,
						'size' => 'wide',
						'theme' => 'white',
					],
					'data' => ['ios' => config('app.stores.ios')],
					'classes' => 'free-trial-launch'])

			</div>
		</div>
	</section>
</div>