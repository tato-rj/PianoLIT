@env('local')
<section class="container">
	<div class="row">		
		<div class="col-lg-8 col-sm-10 col-12 mx-auto text-center">
			<h1 class="mb-3"><strong>Find music that inspires you.</strong></h1>
			<p class="text-muted mb-4" style="font-size: 110%">Where pianists discover new pieces and find inspiration<br>to play only what they love.</p>			
		</div>
	</div>
</section>

@include('search.components.forms.app')

<section class="container mb-5">
	<div class="row">	
		<div class="col-lg-8 col-md-10 col-10 mx-auto row no-gutters">
			<div class="col-lg-7 col-md-6 col-12 mb-3">
				<p><small><strong>WHAT CAN YOU DO WITH PIANOLIT</strong></small></p>
				<ul class="list-flat">
					<li class="mb-2">@fa(['icon' => 'check', 'color' => 'green'])<strong>Find</strong> pieces that match your level</li>
					<li class="mb-2">@fa(['icon' => 'check', 'color' => 'green'])<strong>Discover</strong> pieces similar to the ones you like</li>
					<li class="mb-2">@fa(['icon' => 'check', 'color' => 'green'])<strong>Explore</strong> repertoire by mood or technique</li>
					<li class="">@fa(['icon' => 'check', 'color' => 'green'])<strong>Learn</strong> with tutorials and practicing tips</li>
				</ul>
			</div>
			<div class="col-lg-5 col-md-6 col-12 mb-3">
				<p><small><strong>HERE ARE SOME EXAMPLES</strong></small></p>
				<ul class="list-flat" id="query-suggestions">
					<li class="mb-2"><a href="{{route('explore.search', ['search' => 'pieces for beginners'])}}">
						@tag(['type' => 'search', 'label' => 'pieces for beginners'])</a></li>
					<li class="mb-2"><a href="{{route('explore.search', ['search' => 'pieces like fur elise'])}}">
						@tag(['type' => 'search', 'label' => 'pieces like fur elise'])</a></li>
					<li class="mb-2"><a href="{{route('explore.search', ['search' => 'pieces by women composers'])}}">
						@tag(['type' => 'search', 'label' => 'pieces by women composers'])</a></li>
					<li class="mb-2"><a href="{{route('explore.search', ['search' => 'repertoire for my left hand'])}}">
						@tag(['type' => 'search', 'label' => 'repertoire for the left hand'])</a></li>
					<li class="mb-2"><a href="{{route('explore.search', ['search' => 'bach little preludes'])}}">
						@tag(['type' => 'search', 'label' => 'bach little preludes'])</a></li>
				</ul>
			</div>
		</div>
	</div>
</section>
@else
<section class="container mb-5">
	<div class="row">		
		<div class="col-lg-8 col-sm-10 col-12 mx-auto text-center">
			<h1 class="mb-3"><strong>Find music that inspires you.</strong></h1>
			<p class="text-muted mb-4" style="font-size: 110%">Where pianists discover new pieces and find inspiration<br>to play only what they love.</p>
			
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

		</div>

		<div id="phone-display" class="col-12 text-center d-flex flex-center">
			<img src="{{asset('images/mockup/main-view.png')}}" style="max-width: 720px">
		</div>
	</div>
</section>
@endenv