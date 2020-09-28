<div class="px-3 pt-3 border-top">
	<button class="btn-raw w-100 text-left new-folder" data-target="#new-folder-container-{{$piece->id}}" id="new-folder-button-{{$piece->id}}">@fa(['icon' => 'plus-circle', 'size' => 'lg', 'color' => 'grey'])<strong class="text-muted">Create a new folder</strong></button>

	<div style="display: none;" id="new-folder-container-{{$piece->id}}">
		<div class="d-flex">
			<div class="flex-grow pr-3 d-flex align-items-center">
				<div>
					@fa(['icon' => 'folder-open', 'color' => 'grey', 'size' => 'lg'])
				</div>
				<input type="text" id="folder-name-{{$piece->id}}" name="name" class="form-control border-bottom rounded-0 p-1" placeholder="Folder name" style="border: 0">
			</div>
			<div class="d-flex">
				<button class="btn btn-primary mr-2 rounded-pill" data-submit="folder" data-name="#folder-name-{{$piece->id}}"
				data-url="{{route('webapp.users.favorites.folders.store', ['piece_id' => $piece->id])}}">Save</button>
				<button class="btn btn-grey-outline cancel-new-folder rounded-pill" data-container="#new-folder-container-{{$piece->id}}" data-target="#new-folder-button-{{$piece->id}}">Cancel</button>
			</div>
		</div>
	</div>
</div>