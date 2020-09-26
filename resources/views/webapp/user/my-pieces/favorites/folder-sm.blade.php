<div class="col-lg-4 col-md-6 col-12 folder-container mb-3">
	<div class="rounded border">
		<div class="rounded-top p-3 cursor-pointer favorite-folder">
			<h6 class="m-0 clamp-1">{{$folder->name}}</h6>
			<small class="text-muted">{{$folder->favorites_count}} {{str_plural('piece', $folder->favorites_count)}}</small>
		</div>
		<div style="display: none;" class="favorite-pieces bg-light px-4 border-top">
			@foreach($folder->favorites as $favorite)
				@include('webapp.components.piece', ['piece' => $favorite->piece])
			@endforeach
		</div>
		<div class="rounded-bottom py-2 px-3 border-top d-flex d-apart">
			<button class="btn-raw">@fa(['icon' => 'edit', 'mr' => 0, 'color' => 'secondary'])</button>
			<button class="btn-raw">@fa(['icon' => 'trash-alt', 'mr' => 0, 'color' => 'red'])</button>
		</div>
	</div>
</div>