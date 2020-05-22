<div class="tab-pane fade" id="tab-score">
	@if($piece->isPublicDomain)
	<div class="text-center mb-4">
		@include('webapp.components.pdfviewer')

		<button id="pdf-download" data-url="{{storage($piece->score_path)}}" class="btn rounded-pill btn-default">@fa(['icon' => 'file-alt'])Download score</button>
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