@forelse($pieces as $piece)

<div class="col-lg-3 col-md-4 col-sm-6 col-12 mb-4">
	<a href="{{route('webapp.discover')}}" class="link-none free-trial-launch">
		<div class="h-100 position-relative mx-1 result-card result-piece shadow-sm bg-white border-{{$piece->level->name}} py-2 px-3 t-2 w-100"
			style="border-radius: 20px; border: 6px solid; overflow: hidden;">
			<div class="d-flex h-100 justify-content-between flex-column t-2 card-content">
				<div class="mb-2" style="">
					<span class="badge badge-pill mb-1 bg-{{$piece->level->name}}">{{$piece->level->name}}</span>
					<p class="mb-0 clamp-2 font-weight-bold" style="max-width: 100%;">{{$piece->simple_name}}</p>
					<p class="clamp-1 m-0 text-muted"><small>by {{$piece->composer->short_name}}</small></p>
				</div>
				<div>
					<div class="text-muted">
						@if($piece->tutorials()->exists())
						<div style="line-height: 1.3"><small>@fa(['icon' => 'video'])Media available</small></div>
						@endif
						@if($piece->hasScore($publicDomain = true))
						<div style="line-height: 1.3"><small>@fa(['icon' => 'glasses'])Score available</small></div>
						@endif
					</div>
				</div>
			</div>
			<div class="card-action position-absolute w-100 text-center t-2 h-100 d-flex flex-center" style="left: 0; bottom: -100%">
				<p class="m-0 font-weight-bold"><i class="fab fa-apple"></i> Learn more</p>
			</div>
		</div>
	</a>
</div>

@empty
<div class="col-12 text-center py-5">
	<h1>@fa(['icon' => 'box-open', 'color' => 'grey'])</h1>
	<p class="text-muted">Sorry, let's try something else!</p>
</div>

@endforelse