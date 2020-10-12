<section class="container mb-5">
	<div class="row">		
		<div class="col-lg-8 col-sm-10 col-12 mx-auto text-center">
			<h1 class="mb-3"><strong>Find music that inspires you.</strong></h1>
			<p class="text-muted mb-4" style="font-size: 110%">Where pianists discover new pieces and find inspiration<br>to play only what they love.</p>
			
			@env('local')
				@include('search.components.forms.app')
			@else
				@button([
					'href' => route('webapp.discover'),
					'label' => 'Start FREE trial',
					'styles' => [
						'shadow' => true,
						'size' => 'wide',
						'theme' => 'primary',
						'mb' => 3
					],
					'data' => ['ios' => config('app.stores.ios')],
					'classes' => 'free-trial-launch'])
				
				<div class="d-flex flex-center flex-wrap">
					<a href="{{config('app.stores.ios')}}" target="_blank" class="mr-2"><img style="width: 128px" src="{{asset('images/apple/download.svg')}}"></a>
					<a href="{{route('webapp.discover')}}"><img style="width: 128px" src="{{asset('images/webapp/download.svg')}}"></a>
				</div>
			@endenv

		</div>

		<div id="phone-display" class="col-12 text-center d-flex flex-center">
			<img src="{{asset('images/mockup/main-view.png')}}" style="max-width: 720px">
		</div>
	</div>
</section>