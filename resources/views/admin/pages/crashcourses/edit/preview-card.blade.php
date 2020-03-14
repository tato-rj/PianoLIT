<div class="mb-4">
  <div class="rounded bg-white border p-3 position-relative">
    <div class="badge alert-yellow position-absolute" style="top: -9px; left: 9px">
      Feedback
    </div>
    <div class="mb-3 pb-2 border-bottom">
      <div>
        <h5 class="m-0"><strong>Feedback email</strong></h5>
        <p class="m-0 text-muted"><small>This email is automatically sent at the end of this course</small></p>
      </div>
    </div>
    <div>
      <a href="#" data-toggle="modal" data-target="#feedback-preview-modal" class="btn btn-sm btn-outline-dark mr-2">
      <i class="far fa-eye mr-2"></i>Preview
      </a>

      <div class="modal fade" id="feedback-preview-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="exampleModalLabel">Preview feedback</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <div>
                <form method="GET" action="{{route('admin.crashcourses.feedback.send-to', 
                  ['crashcourse' => $crashcourse, 'first_name' => auth()->user()->first_name]
                  )}}" class="mb-2" disable-on-submit>
                  @csrf
                  @input([
                    'label' => 'Send a preview to', 
                    'value' => null, 
                    'name' => 'email', 
                    'bag' => 'default', 
                    'asterisk' => true])
                  <button type="submit" class="btn btn-sm btn-block btn-default">Send preview</button>
                </form>

                <a href="{{route('admin.crashcourses.feedback.preview', ['crashcourse' => $crashcourse, 'first_name' => auth()->user()->first_name])}}" id="preview-url" target="_blank" class="btn btn-outline-secondary btn-block btn-sm px-3 mr-2">Or just preview in the browser</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>