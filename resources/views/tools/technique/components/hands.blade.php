<div class="col-lg-8 col-md-10 col-12 mx-auto">
	<div class="row">
		<div class="col-6">
			<button class="btn btn-light d-block w-100 play-notes pb-4 pt-3"
				data-target="#keyboard-{{str_slug($result['name'])}}"
				data-notes-target="#notes-{{str_slug($result['name'])}}"
				data-fingering-target="#fingering-{{str_slug($result['name'])}}"
				data-label="LEFT HAND FINGERING"
				data-fingering="{{json_encode($result['lh'])}}"
				data-notes="{{json_encode($result['up_down'])}}">
				<div class="text-muted mb-2"><small><strong>PLAY LEFT HAND</strong></small></div>
				@icon('hand', ['mr' => 0, 'classes' => 'opacity-6 text-grey icon-flip-horizontal icon-size-8x'])
			</button>
		</div>
		<div class="col-6">
			<button class="btn btn-light d-block w-100 play-notes pb-4 pt-3"
				data-target="#keyboard-{{str_slug($result['name'])}}"
				data-notes-target="#notes-{{str_slug($result['name'])}}"
				data-fingering-target="#fingering-{{str_slug($result['name'])}}"
				data-label="RIGHT HAND FINGERING"
				data-fingering="{{json_encode($result['rh'])}}"
				data-notes="{{json_encode($result['up_down'])}}">
				<div class="text-muted mb-2"><small><strong>PLAY RIGHT HAND</strong></small></div>
				@icon('hand', ['mr' => 0, 'classes' => 'opacity-6 text-grey icon-size-8x'])
			</button>
		</div>
	</div>
</div>	