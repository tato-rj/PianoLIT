<div id="audio-player">
	<div id="player-header" class="d-flex px-3 py-2 cursor-pointer">
		@include('webapp.piece.components.player.header')
	</div>
	<div id="player-body" class="p-3 border-top">
		<div class="d-flex mb-3">
			@include('webapp.piece.components.player.hands')
			@include('webapp.piece.components.player.speed-control')
		</div>
		<div>
			@include('webapp.piece.components.player.audio')
		</div>
	</div>
</div>