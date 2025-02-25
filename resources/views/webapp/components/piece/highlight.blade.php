<div class=" cursor-pointer bg-align-center rounded d-flex d-apart flex-column p-3 piece-card" role="img" aria-label="{{$piece->name}}" data-url="{{route('webapp.pieces.show', $piece)}}" style="background-image: url({{$piece->image_background}}); height: {{$height ?? '200px'}}; width: {{$width ?? '100%'}}">
	<div class="w-100 text-white">
		<p class="h6 m-0 text-white clamp-2">{{$piece->name}}</p>
		<p class="m-0 text-white">{{$piece->attribution}}{{$piece->composer->short_name}}</p>
	</div>
	<div class="w-100 text-white">
		@fa(['icon' => 'circle', 'mr' => 1, 'classes' => 'align-middle color-' . $piece->level_name])
		<span><small>{{strtoupper($piece->extended_level_name)}}</small></span>
	</div>
</div>