<a href="#" data-toggle="modal" data-target="#lesson-{{$lesson->id}}-preview-modal" class="btn btn-sm btn-outline-dark mr-2">
<i class="far fa-eye mr-2"></i>Preview
</a>

<div class="modal fade" id="lesson-{{$lesson->id}}-preview-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Preview lesson</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
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
            <button type="submit" class="btn btn-sm btn-block btn-default">Send preview</button>
          </form>

          <a href="{{route('admin.crashcourses.lessons.preview', compact(['crashcourse', 'lesson']))}}" id="preview-url" target="_blank" class="btn btn-outline-secondary btn-block btn-sm px-3 mr-2">Or just preview in the browser</a>
        </div>
      </div>
    </div>
  </div>
</div>