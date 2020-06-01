<div class="text-center text-muted mb-2">
	<small>user the controls @fa(['icon' => 'arrow-alt-circle-left', 'color' => 'grey', 'mr' => 0]) and @fa(['icon' => 'arrow-alt-circle-right', 'color' => 'grey', 'mr' => 0]) to flip the pages</small>
</div>
<div class="position-relative mb-4 border" id="pdf-container">
	<div class="pdf-control" style="display: none;" left>
		<div class="cursor-pointer position-absolute d-flex justify-content-start align-items-center px-2 h-100" style="top: 0; left: 0; width: 30%;">
			<button class="btn-raw text-grey t-2" style="opacity: .2">@fa(['icon' => 'arrow-alt-circle-left', 'mr' => 0, 'size' => '4x'])</button>
		</div>
	</div>
	<div class="pdf-control" right>
		<div class="cursor-pointer position-absolute d-flex justify-content-end align-items-center px-2 h-100" style="top: 0; right: 0; width: 30%">
			<button class="btn-raw text-grey t-2" style="opacity: .2">@fa(['icon' => 'arrow-alt-circle-right', 'mr' => 0, 'size' => '4x'])</button>
		</div>
	</div>
	<div id="pdf-loading" class="position-absolute w-100 h-100 bg-white opacity-4" style="top: 0; left: 0; display: none;">
		<div class="d-flex flex-center w-100 h-100">
			<div class="spinner-border text-secondary" style="width: 4rem; height: 4rem;" role="status">
			  <span class="sr-only">Loading...</span>
			</div>
		</div>
	</div>
	<canvas id="score-pdf" class="" style="width: 100%"></canvas>
</div>