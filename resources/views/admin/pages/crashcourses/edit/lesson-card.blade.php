<div class="mb-4 ordered" data-id="{{$lesson->id}}">
  <div class="rounded bg-white hover-shadow-light t-2 border p-3 position-relative sort-handle" title="Drag to reorder">
    <div class="badge alert-teal position-absolute" style="top: -9px; left: 9px">
      Lesson {{$lesson->order + 1}}
    </div>
    <div class="mb-3 pb-2 border-bottom d-flex justify-content-between">
      <div>
        <h5 class="m-0"><strong>{{$lesson->subject}}</strong></h5>
        <p class="m-0 text-muted"><small>@icon('timer', ['mr' => 2]){{$lesson->reading_time}} min read</small></p>
      </div>
      <div class="px-2 mt-2">
        @icon('arrow-down-up', ['mr' => 0, 'classes' => 'icon-size-lg'])
      </div>
    </div>
    <div class="d-flex justify-content-between">
      <div class="">
        @include('admin.pages.crashcourses.lessons.actions', ['id' => $lesson->id])
        @include('admin.components.table-action', ['tableAction' => ['kind' => 'edit', 'url' => route('admin.crashcourses.lessons.edit', compact(['crashcourse', 'lesson']))]])
        @include('admin.components.table-action', ['tableAction' => ['url' => route('admin.crashcourses.lessons.duplicate', compact(['crashcourse', 'lesson'])), 'label' => 'Duplicate', 'icon' => 'copy']])
      </div>
      <div>
        @include('admin.components.table-action', ['tableAction' => ['kind' => 'delete', 'iconOnly' => false, 'class' => 'delete', 'attributes' => ['data-url' => route('admin.crashcourses.lessons.destroy', compact(['crashcourse', 'lesson'])), 'data-bs-toggle' => 'modal', 'data-bs-target' => '#delete-modal']]])
      </div>
    </div>
  </div>
</div>