<a href="#" data-bs-toggle="modal" data-bs-target="#lesson-{{$lesson->id}}-preview-modal" class="btn btn-sm btn-outline-dark me-2">
@icon('eye', ['mr' => 2])Preview
</a>

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
            <button type="submit" class="btn btn-sm d-block w-100 btn-default">Send preview</button>
          </form>

          <a href="{{route('admin.crashcourses.lessons.preview', compact(['crashcourse', 'lesson']))}}" id="preview-url" target="_blank" class="btn btn-outline-secondary d-block w-100 btn-sm px-3 me-2">Or just preview in the browser</a>
        </div>
      </div>
    </div>
  </div>
</div>