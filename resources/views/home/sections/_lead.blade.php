<section class="container">
	<div class="row">		
		<div class="col-lg-8 col-sm-10 col-12 mx-auto text-center">
			<h1 class="mb-3 font-weight-bold animate-letters">Find classical piano pieces that inspires you.</h1>
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
					@foreach($suggestions as $suggestion)
					<li class="mb-2" style="display: {{$loop->iteration > 5 ? 'none' : null}}">
						<a href="{{route('explore.search', ['search' => $suggestion])}}">
						@tag(['type' => 'search', 'label' => $suggestion])</a></li>
					@endforeach
				</ul>
			</div>
		</div>
	</div>
</section>