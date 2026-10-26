<div class="text-center mb-3" id="keyboard-wrapper">
	<div class="position-relative d-inline-block" style="max-width: 100%">
		<div id="keyboard" class="d-flex justify-content-center">
			@for($i=0;$i<=6;$i++)
			@include('games.challenges.components.piano.key', ['blackKey' => ! in_array($i, [2,6])])
			@endfor
		</div>
	</div>
</div>
