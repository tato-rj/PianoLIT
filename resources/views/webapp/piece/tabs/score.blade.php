<div class="tab-pane fade" id="tab-score">
	@if($piece->isPublicDomain)
	<div class="text-center mb-4">
		<div class="position-relative" id="pdf-container">
			<canvas id="score-pdf" class="w-100 border"></canvas>
			<div class="pdf-control cursor-pointer position-absolute d-flex justify-content-start align-items-center px-2 h-100" style="top: 0; left: 0; width: 30%" onclick="showPrevPage()">
				<button class="btn-raw text-grey t-2" style="opacity: .2">@fa(['icon' => 'arrow-alt-circle-left', 'mr' => 0, 'size' => '4x'])</button>
			</div>
			<div class="pdf-control cursor-pointer position-absolute d-flex justify-content-end align-items-center px-2 h-100" style="top: 0; right: 0; width: 30%" onclick="showNextPage()">
				<button class="btn-raw text-grey t-2" style="opacity: .2">@fa(['icon' => 'arrow-alt-circle-right', 'mr' => 0, 'size' => '4x'])</button>
			</div>
		</div>
		<a href="{{route('webapp.pieces.score', $piece)}}" class="btn rounded-pill btn-default">@fa(['icon' => 'file-alt'])Download score</a>
	</div>
	@else
	<div class="text-center mb-4">
		<p class="text-muted">This piece is protected by copyrights. Click the button below and we'll show you where you can purchase the score!</p>
		<a href="{{$piece->score_url}}" target="_blank" class="btn rounded-pill btn-default">@fa(['icon' => 'shopping-basket'])Buy score</a>
	</div>
	@endif

	<div>
		<p class="m-0"><strong>Editor</strong></p>
		<p class="text-muted">{{$piece->score_editor ?? 'Unknown'}}</p>
		<p class="m-0"><strong>Publisher info</strong></p>
		<p class="text-muted">{{$piece->score_publisher ?? 'Unknown'}}</p>
		<p class="m-0"><strong>Copyright</strong></p>
		<p class="text-muted">{{$piece->score_copyright ?? 'Unknown'}}</p>
	</div>
</div>