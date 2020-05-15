<section class="my-4 pb-4 d-flex d-apart">
	<a href="{{route('webapp.pieces.show', $piece)}}" class="btn-raw d-flex text-left align-items-center link-none">
		<div style="font-size: 2em" class="mr-2">@fa(['icon' => 'chevron-left'])</div>
		<div>
			<div style="line-height: 1" class="mb-1"><small><strong>RETURN TO</strong></small></div>
			<div style="line-height: 1"><small>{{$piece->medium_name}}</small></div>
			<div style="line-height: 1" class="text-muted"><small>{{$piece->composer->name}}</small></div>
		</div>
	</a>

	<button class="btn-raw" type="More options"	data-toggle="fixed-panel" data-target="#options-panel" style="font-size: 1.44em">
		@fa(['icon' => 'ellipsis-v'])</button>
</section>