<div class="">
	<div class="cursor-pointer">
		<div class="audio-control text-center">
			<button class="border-0 rounded-circle btn-green play-control" style="height: 40px; width: 40px" data-action="play">
				@fa(['icon' => 'play', 'mr' => 0])
			</button>
			<button class="border-0 rounded-circle btn-secondary play-control" style="display: none; height: 40px; width: 40px" data-action="pause">
				@fa(['icon' => 'pause', 'mr' => 0])
			</button>
			<audio style="display: none;">
				<source src="{{storage($piece->audio_path)}}" type="audio/mpeg">
			</audio>
		</div>
	</div>
</div>