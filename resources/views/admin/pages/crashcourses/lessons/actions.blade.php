@include('admin.components.table-action', ['tableAction' => ['label' => 'Preview', 'icon' => 'eye', 'attributes' => ['data-bs-toggle' => 'modal', 'data-bs-target' => '#lesson-'.$lesson->id.'-preview-modal']]])

<div class="modal fade" id="lesson-{{$lesson->id}}-preview-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Preview lesson</h5>
        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div>
          <form method="GET" action="{{route('admin.crashcourses.lessons.send-to', compact(['crashcourse', 'lesson']))}}" class="mb-2" disable-on-submit>
            @csrf
            @input([
              'label' => 'Send a preview to',
              'value' => null,
              'name' => 'email',
              'bag' => 'default',
              'asterisk' => true])
            @include('admin.components.table-action', ['tableAction' => ['type' => 'submit', 'label' => 'Send preview', 'class' => 'd-block w-100']])
          </form>

          @include('admin.components.table-action', ['tableAction' => ['url' => route('admin.crashcourses.lessons.preview', compact(['crashcourse', 'lesson'])), 'label' => 'Or just preview in the browser', 'class' => 'd-block w-100', 'attributes' => ['id' => 'preview-url', 'target' => '_blank', 'rel' => 'noopener noreferrer']]])
        </div>
      </div>
    </div>
  </div>
</div>