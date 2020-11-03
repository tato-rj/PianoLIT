<div class="bg-primary text-white">
	<section class="container mb-5">
		<div class="row align-items-center py-5">
			<div class="col-lg-6 col-md-6 col-sm-6 col-12">
				<div class="p-2 my-2">
					<h2>What is PianoLIT?</h2>
					<p class="lead" style="font-size: 120%">PianoLIT is the first search engine for pianists that helps you navigate the repertoire and find pieces that match your level, mood, and interest.</p>
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
			<div class="col-lg-6 col-md-6 col-sm-6 col-12">
				<div class="p-2 my-2 shadow bg-white" style="  position: relative;
  overflow: hidden;
  width: 100%;
  height: 315px;
  padding-top: 56.25%;">
					<iframe style="  position: absolute;
  top: 0;
  left: 0;
  bottom: 0;
  right: 0;
  width: 100%;
  height: 100%;" src="https://www.youtube.com/embed/QtuwPzXT7D0?controls=0" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
				</div>
			</div>
		</div>
	</section>
</div>