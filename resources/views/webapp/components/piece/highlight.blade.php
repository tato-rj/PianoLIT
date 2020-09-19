<div class=" cursor-pointer bg-align-center rounded d-flex d-apart flex-column p-3 piece-card" data-url="{{route('webapp.pieces.show', $piece)}}" style="background-image: url({{$piece->background}}); height: {{$height ?? '200px'}}; width: {{$width ?? null}}">
	<div class="w-100 text-white">
		<h6 class="m-0 text-white clamp-2">{{$piece->name}}</h6>
		<p class="m-0 text-white">{{$piece->composer->short_name}}</p>
	</div>
	<div class="w-100 text-white">
		@fa(['icon' => 'circle', 'mr' => 1, 'classes' => 'align-middle color-' . $piece->level_name])
		<span><small>{{strtoupper($piece->extended_level_name)}}</small></span>
	</div>
</div>