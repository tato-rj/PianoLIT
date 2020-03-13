<div class="mb-4 ordered" data-id="{{$lesson->id}}">
  <div class="rounded bg-white hover-shadow-light t-2 border p-3 position-relative sort-handle" title="Drag to reorder">
    <div class="badge alert-teal position-absolute" style="top: -9px; left: 9px">
      Lesson {{$lesson->order + 1}}
    </div>
    <div class="mb-3 pb-2 border-bottom d-flex justify-content-between">
      <div>
        <h5 class="m-0"><strong>{{$lesson->subject}}</strong></h5>
        <p class="m-0 text-muted"><small><i class="fas fa-stopwatch mr-2"></i>{{$lesson->reading_time}} min read</small></p>
      </div>
      <div class="px-2 mt-2">
        <i class="fas fa-sort fa-lg"></i>
      </div>
    </div>
    <div class="d-flex justify-content-between">
      <div class="">
        @include('admin.pages.crashcourses.lessons.actions')
        <a href="{{route('admin.crashcourses.lessons.edit', compact(['crashcourse', 'lesson']))}}" class="btn btn-sm btn-warning">
          <i class="far fa-edit mr-2"></i>Edit
        </a>
      </div>
      <div>
        <a href="#" data-url="{{route('admin.crashcourses.lessons.destroy', compact(['crashcourse', 'lesson']))}}" title="Delete" data-toggle="modal" data-target="#delete-modal" class="delete btn btn-sm btn-danger">
          <i class="far fa-trash-alt mr-2"></i>Delete
        </a>
      </div>
    </div>
  </div>
</div>