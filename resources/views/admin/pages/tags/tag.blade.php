<div class="t-2 hover-shadow-light bg-light tag cursor-pointer text-muted d-flex align-items-clenter border rounded px-3 py-2 m-2" 
	 style="border-color: lightgrey"
	 data-name="{{$tag->name}}"
	 data-creator="{{$tag->creator->name}}"
	 data-type="{{$tag->type}}"
	 data-edit-url="{{route('admin.tags.update', $tag->id)}}" 
	 data-delete-url="{{route('admin.tags.destroy', $tag->id)}}" 
	 data-toggle="modal" 
	 data-target="#tag-modal">
  <p class="m-0">{{$tag->name}}</p>
  @if(\App\Tag::special()->get()->contains($tag))
  <i class="fas fa-star text-warning ml-2 fa-xs" style="margin-right: -10px"></i>
  @endif
</div>