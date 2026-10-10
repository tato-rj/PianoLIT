<div class="t-2 hover-shadow-light bg-light tag cursor-pointer text-muted d-flex align-items-clenter border rounded px-3 py-2 m-2"
	 style="border-color: lightgrey"
	 data-name="{{$tag->name}}"
	 data-creator="{{$tag->creator->name}}"
	 data-type="{{$tag->type}}"
	 data-ordering="{{$tag->ordering}}"
	 data-edit-url="{{route('admin.tags.update', $tag->id)}}"
	 data-delete-url="{{route('admin.tags.destroy', $tag->id)}}"
	 data-bs-toggle="modal"
	 data-bs-target="#tag-modal">
  <p class="m-0">{{$tag->name}}</p>
</div>
