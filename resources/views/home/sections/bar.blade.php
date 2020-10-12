<div style="background: #0055fe; color: white">
	<section class="container mb-5">
		<div class="row align-items-center py-7">
			<div class="col-lg-6 col-md-6 col-sm-6 col-12">
				<h1 class="my-2">Curious?<br>Try it for free</h1>
			</div>
			<div class="col-lg-6 col-md-6 col-sm-6 col-12">
				<p class="mb-4" style="font-size: 110%">You can try PianoLIT for <u>free for 7 days</u>. Don’t worry, if you cancel within the trial period you won’t be charged.</p>
	
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