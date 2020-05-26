<div class="text-center text-muted mb-2">
	<small>user the controls @fa(['icon' => 'arrow-alt-circle-left', 'color' => 'grey', 'mr' => 0]) and @fa(['icon' => 'arrow-alt-circle-right', 'color' => 'grey', 'mr' => 0]) to flip the pages</small>
</div>
<div class="position-relative mb-4 border" id="pdf-container">
	<div class="pdf-control" left>
		<div class="cursor-pointer position-absolute d-flex justify-content-start align-items-center px-2 h-100" style="top: 0; left: 0; width: 30%;">
			<button class="btn-raw text-grey t-2" style="opacity: .2">@fa(['icon' => 'arrow-alt-circle-left', 'mr' => 0, 'size' => '4x'])</button>
		</div>
	</div>
	<div class="pdf-control" right>
		<div class="cursor-pointer position-absolute d-flex justify-content-end align-items-center px-2 h-100" style="top: 0; right: 0; width: 30%">
			<button class="btn-raw text-grey t-2" style="opacity: .2">@fa(['icon' => 'arrow-alt-circle-right', 'mr' => 0, 'size' => '4x'])</button>
		</div>
	</div>
	<canvas id="score-pdf" class="" style="width: 100%"></canvas>
</div>