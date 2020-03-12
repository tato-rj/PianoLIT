<div class="t-2 hover-shadow-light bg-light topic cursor-pointer text-muted d-flex align-items-clenter border rounded px-3 py-2 m-2" 
	 style="border-color: lightgrey"
	 data-name="{{$topic->name}}"
	 data-creator="{{$topic->creator->name}}"
	 data-edit-url="{{route('admin.crashcourses.topics.update', $topic->slug)}}" 
	 data-delete-url="{{route('admin.crashcourses.topics.destroy', $topic->slug)}}" 
	 data-toggle="modal" 
	 data-target="#topic-modal">
  <p class="m-0">{{$topic->name}}</p>
</div>