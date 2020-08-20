<div class="col-lg-3 col-md-4 col-sm-6 col-12 mb-4">
	<a href="https://my.pianolit.com" class="link-none free-trial-launch">
		<div class="h-100 position-relative mx-1 result-card result-piece shadow-sm bg-white border-{{$piece->level->name}} py-2 px-3 t-2 w-100"
			style="border-radius: 20px; border: 6px solid; overflow: hidden;">
			<div class="d-flex h-100 justify-content-between flex-column t-2 card-content">
				<div class="mb-2" style="">
					<span class="badge badge-pill mb-1 bg-{{$piece->level->name}}">{{$piece->level->name}}</span>
					<p class="mb-0 clamp-2" style="max-width: 100%;"><strong>{{$piece->simple_name}}</strong></p>
					<p class="clamp-1 m-0 text-muted"><small>by {{$piece->composer->short_name}}</small></p>
				</div>
				<div>
					<div class="text-muted">
						@if($piece->hasItunes())
						<div style="line-height: 1.3"><small><i class="fab fa-itunes"></i> iTunes recordings</small></div>
						@endif
						@if($piece->tutorials()->exists())
						<div style="line-height: 1.3"><small><i class="fab fa-youtube"></i> Video available</small></div>
						@endif
						@if($piece->hasAudio())
						<div style="line-height: 1.3"><small><i class="fas fa-headphones-alt"></i> Audio available</small></div>
						@endif
					</div>
				</div>
			</div>
			<div class="card-action position-absolute w-100 text-center t-2 h-100 d-flex flex-center" style="left: 0; bottom: -100%">
				<h6 class="m-0"><i class="fab fa-apple"></i> Learn more</h6>
			</div>
		</div>
	</a>
</div>