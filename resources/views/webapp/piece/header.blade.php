<section class="text-center mb-5 position-relative">
	@include('webapp.components.piece.level')
	<h4 class="mt-2 mb-1 px-3">{{$piece->medium_name}}</h4>
	<p class="text-muted">{{$piece->composer->name}}</p>

	<button class="btn-raw position-absolute" type="More options" style="right: 0; bottom: 50%; transform: translateY(50%); font-size: 1.44em">@fa(['icon' => 'ellipsis-v'])</button>
</section>