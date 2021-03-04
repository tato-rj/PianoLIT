<div class="col-lg-3 col-md-4 col-6 p-2">
	<div class="rounded cursor-pointer list-group-item list-group-item-action border-0 cursor-pointer w-100 p-3" data-carousel="answer" data-type="multi" value="{{$piece->id}}">
		<div class="audio-control text-center mb-2">
			<button class="border-0 rounded-circle btn-green play-control" style="height: 50px; width: 50px" data-action="play">
				@fa(['icon' => 'play', 'size' => 'lg', 'mr' => 0])
			</button>
			<button class="border-0 rounded-circle btn-secondary play-control" style="display: none; height: 50px; width: 50px" data-action="pause">
				@fa(['icon' => 'pause', 'size' => 'lg', 'mr' => 0])
			</button>
			<audio style="display: none;">
				<source src="{{storage($piece->audio_path)}}" type="audio/mpeg">
			</audio>
		</div>
		<div class="text-center">
			<div style="line-height: 1" class="mb-1">{{$piece->name}}</div>
			<div style="line-height: 1" class="opacity-8"><small>by {{$piece->composer->short_name}}</small></div>
		</div>
	</div>
</div>