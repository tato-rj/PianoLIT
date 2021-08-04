<div class="tab-pane fade" id="tab-score">
	{{-- @if($piece->hasAudio()) --}}
	<div class="mb-4">
		{{-- <h5 class="mb-4">Audio recording</h5> --}}
		<div class="text-center">
			<button id="launch-audio" data-url="{{route('webapp.pieces.audio', $piece)}}" class="btn btn-outline-secondary btn-wide">
				@fa(['icon' => 'microphone', 'size' => '1x'])Listen now</button>
		</div>
	</div>
	{{-- @endif --}}
	
	@if($piece->isPublicDomain)
	<div class="non-ios" style="display: none;">
		<div class="text-center mb-4">
			@include('webapp.components.pdfviewer')

			<a href="{{storage($piece->score_path)}}" target="_blank" class="btn rounded-pill btn-default">@fa(['icon' => 'file-alt'])Download score</a>
		</div>
	</div>
	<div class="ios-only w-100" style="display: none;">
		<div class="embed-responsive embed-responsive-a4 mb-4 position-relative">
			<embed type="application/pdf" src="{{storage($piece->score_path)}}" class="embed-responsive-item" frameborder="0">
			<div class="w-100 h-100 position-absolute d-flex flex-center" style="top: 0; left: 0; background-color: rgba(255,255,255,0.6)">
				<div class="d-flex flex-column">
					<a href="{{storage($piece->score_path)}}" target="_blank" class="btn rounded-pill btn-default shadow-center mb-2">@fa(['icon' => 'file-alt'])Download score</a>
					<button id="pdf-share" data-url="{{storage($piece->score_path)}}" class="btn border rounded-pill shadow-center btn-light">@fa(['icon' => 'share-square'])Share score</button>
				</div>
			</div>
		</div>
	</div>
	@else
	<div class="text-center mb-4">
		<p class="text-muted">This piece is protected by copyrights. Click the button below and we'll show you where you can purchase the score!</p>
		<a href="{{$piece->score_url}}" target="_blank" class="btn rounded-pill btn-default">@fa(['icon' => 'shopping-basket'])Buy score</a>
	</div>
	@endif

	<div>
		{{-- <p class="m-0"><strong>Editor</strong></p> --}}
		{{-- <p class="text-muted">{{$piece->score_editor ?? 'Unknown'}}</p> --}}
		{{-- <p class="m-0"><strong>Publisher info</strong></p> --}}
		{{-- <p class="text-muted">{{$piece->score_publisher ?? 'Unknown'}}</p> --}}
		{{-- <p class="m-0"><strong>Copyright</strong></p> --}}
		{{-- <p class="text-muted">{{$piece->score_copyright ?? 'Unknown'}}</p> --}}
	</div>
</div>