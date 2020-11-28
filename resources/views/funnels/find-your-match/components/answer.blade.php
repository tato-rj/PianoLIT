<div class="col-lg-4 col-md-6 col-sm-6 col-12 mb-3">
	<div class="hover-shadow border t-2 w-100 answer-card">
		<div class="position-relative">
			<div class=" bg-align-center cursor-pointer answer-trigger" style="background-image: url(https://picsum.photos/{{rand(200, 300)}}/{{rand(200,300)}}); height: 200px;">
			</div>
			@isset($audio)
				@include('funnels.find-your-match.components.play')
			@endisset
		</div>

		<div class="p-2">
			<p class="m-0 font-weight-bold" style="line-height: 1.2">{{$label}}</p>
			<div class="opacity-8" style="line-height: 1.2"><small><i>{{$subtitle ?? null}}</i></small></div>
		</div>
	</div>
</div>