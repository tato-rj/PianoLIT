<div class="tab-pane fade" id="tab-lessons">
	@if($piece->tutorials()->exists())
	<div class="mb-5">
		@each('webapp.piece.components.video', $piece->lessons, 'tutorial')
	</div>
	@else
	<div class="text-center py-4">
		<p class="m-0">No media found :/</p>
		<p class="text-muted">We haven't uploaded any audio or videos yet...</p>
		@fa(['icon' => 'music', 'color' => 'grey', 'size' => 'lg'])
	</div>
	@endif

	@if(! $piece->hasTutorials(['tutorial', 'harmony']))
	<div class="row">
		<div class="col-lg-7 col-md-10 col-12 mx-auto text-center border-top mt-3 pt-3">
			<p class="text-muted">Would you like to learn more about this piece? Request video tutorials to learn more</p>
			<button class="btn rounded-pill btn-outline-secondary btn-wide" data-toggle="modal" data-target="#tutorial-request-modal">Send my request</button>
		</div>
	</div>

	@include('webapp.piece.tutorial-request')
	@endif
</div>