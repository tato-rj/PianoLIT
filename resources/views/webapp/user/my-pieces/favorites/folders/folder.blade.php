<div class="col-lg-4 col-md-4 col-6 folder-container mb-3">
	<div class="rounded border">
		<a href="{{route('webapp.users.favorites.folders.show', $folder)}}" class="link-none">
			<div class="rounded-top p-3 cursor-pointer favorite-folder">
				<h6 class="mb-1 text-truncate">@fa(['icon' => 'folder-open', 'color' => 'grey']){{$folder->name}}</h6>
				@include('webapp.user.my-pieces.favorites.folders.pieces-count')
			</div>
		</a>
		<div class="p-2">
			<div class="rounded border py-1 px-3 bg-light d-flex d-apart">
				<button class="btn-raw" data-toggle="modal" data-target="#edit-folder-{{$folder->id}}">@fa(['icon' => 'edit', 'mr' => 0, 'color' => 'secondary'])</button>
				<button class="btn-raw" data-toggle="modal" data-target="#delete-folder-{{$folder->id}}">@fa(['icon' => 'trash-alt', 'mr' => 0, 'color' => 'red'])</button>
			</div>
		</div>
	</div>
</div>