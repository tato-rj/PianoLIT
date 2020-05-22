<div class="tab-pane fade" id="tab-score">
	@if($piece->isPublicDomain)
	<div class="text-center mb-4">
	<div class="position-relative">
		<canvas id="score-pdf" class="w-100 border"></canvas>
		<div class="position-absolute w-100 d-flex d-apart" style="top: 50%; left: 0; transform: translateY(-50%);">
			<button onclick="showPrevPage()">BACK</button>
			<button onclick="showNextPage()">FORWARD</button>
		</div>
	</div>
{{-- 		<div class="embed-responsive embed-responsive-a4 mb-4">
			<embed type="application/pdf" src="{{storage($piece->score_path)}}" class="embed-responsive-item" frameborder="0">
		</div> --}}
		<a href="{{storage($piece->score_path)}}" target="_blank" class="btn rounded-pill btn-default">@fa(['icon' => 'file-alt'])Download score</a>
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