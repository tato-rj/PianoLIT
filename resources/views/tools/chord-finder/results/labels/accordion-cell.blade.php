<div class="card border-0 mb-2">
	<div data-toggle="collapse" data-target="#chord-{{str_slug($title)}}" class="alert-grey px-4 py-3 cursor-pointer border-pill">
		<h6 class="mb-0">
			<strong class="mr-2 opacity-4">STEP {{$step}}</strong>{{$title}}
		</h6>
	</div>

	<div id="chord-{{str_slug($title)}}" class="collapse {{$show ?? null}}" data-parent="#chord-accordion-{{$index}}">
		<div class="card-body">
			{{$slot}}
		</div>
	</div>
</div>