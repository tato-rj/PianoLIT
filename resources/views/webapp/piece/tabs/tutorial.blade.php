<div class="tab-pane fade" id="tab-tutorial">
	@if(!$piece->synthesia()->exists())
	<div class="mb-5">
		@video([
			'classes' => 'w-100', 
			'id' => 'piece-synthesia', 
			'thumbnail' => asset('images/webapp/synthesia-thumbnail.gif'),
			'url' => $piece->synthesia()->video_url])
	</div>
	@else

	<div class="text-center pt-5 pb-6">
		<p class="text-muted">Would you like to watch a synthesia of this piece?<br>Tap below to make your request.</p>
		<button class="btn rounded-pill btn-outline-secondary btn-wide" data-toggle="modal" data-target="#synthesia-request-modal">Send my request</button>
	</div>

	@include('webapp.piece.synthesia-request')
	@endif

	<h5 class="mb-3">What is Synthesia?</h5>
	<p>Synthesia is a game that can help you learn how to play the piano using falling notes. It lowers the barrier to entry for beginners.</p>
	<p>You can get started immediately without knowing how to read sheet music. Playing pieces right away provides great motivation to stick with the piano where you can learn traditional sheet music notation over time as you go along.</p>


</div>