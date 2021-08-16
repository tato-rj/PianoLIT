<div class="position-fixed w-100 h-100vh fixed-panel" id="options-panel" style="z-index: 100000000; top: 0; left: 0; display: none;">
	@include('components.panel.overlay')

	<div class="bg-white position-absolute h-100 panel-content" style="right: -100%; transition: .4s">
		<div class="panel-header px-4 py-3">
			<div class="d-flex d-apart">
				<h5 class="m-0">Options</h5>
				<button type="button" style="margin-bottom: 1px" class="close" data-dismiss="fixed-panel" aria-label="Close">
					<span class="mb-1" aria-hidden="true">&times;</span>
				</button>
			</div>
		</div>

		<div class="panel-body px-2 py-3" style="overflow-y: auto; height: 85%;">
			<div class="list-group">
				<a href="{{route('webapp.pieces.composer', $piece)}}" class="link-none mb-3 px-3 d-flex d-apart">Meet the composer @fa(['icon' => 'chevron-right', 'color' => 'muted', 'mr' => 0, 'ml' => 4])</a>

				@if($piece->siblingsExist())
				<a href="{{route('webapp.pieces.collection', $piece)}}" class="link-none mb-3 px-3 d-flex d-apart">From the same collection @fa(['icon' => 'chevron-right', 'color' => 'muted', 'mr' => 0, 'ml' => 4])</a>
				@endif

				<a href="{{route('webapp.pieces.similar', $piece)}}" class="link-none mb-3 px-3 d-flex d-apart">More like this @fa(['icon' => 'chevron-right', 'color' => 'muted', 'mr' => 0, 'ml' => 4])</a>

				<a href="{{route('webapp.pieces.timeline', $piece)}}" class="link-none mb-3 px-3 d-flex d-apart">Timeline @fa(['icon' => 'chevron-right', 'color' => 'muted', 'mr' => 0, 'ml' => 4])</a>

				<div class="dropdown-divider"></div>
				
				<div class="py-2 list-group">
					<a class="cursor-pointer share-piece link-none mb-3 px-3" data-toggle="modal" data-target="#share-modal">
						@fa(['icon' => 'share']) Share this piece
					</a>

					<a class="cursor-pointer toggle-favorite link-none mb-3 px-3">
						@include('webapp.components.favorite')<span>Manage favorites</span>
					</a>
				</div>
			</div>
		</div>
	</div>
</div>