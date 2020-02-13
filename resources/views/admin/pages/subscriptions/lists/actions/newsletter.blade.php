<a href="#" data-toggle="modal" data-target="#{{str_slug($list->name)}}-send-modal" class="btn btn-warning btn-sm px-3">Actions</a>

<div class="modal fade" id="{{str_slug($list->name)}}-send-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Send email list</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="alert-grey px-3 py-2 rounded mb-4">
          @input([
            'label' => 'Newsletter subject', 
            'value' => null, 
            'name' => 'subject-input',
            'placeholder' => 'PianoLIT Newsletter',
            'bag' => 'default'])
        </div>
        <div class="mb-4">
          <form method="GET" action="{{route('admin.subscriptions.lists.send-to', $list)}}" class="mb-2" disable-on-submit>
            @csrf
            <input type="hidden" name="subject">
            @input([
              'label' => 'Send a preview email to', 
              'value' => null, 
              'name' => 'email', 
              'bag' => 'default', 
              'asterisk' => true])
            <button type="submit" class="btn btn-sm btn-block btn-default">Send preview</button>
          </form>

          <a href="{{route('admin.subscriptions.lists.preview', $list)}}" id="preview-url" target="_blank" class="btn btn-outline-secondary btn-block btn-sm px-3 mr-2">Or just preview in the browser</a>
        </div>
        <div class="bg-light px-3 py-2">
          By clicking the button below you will send this email to the entire list.
          <p class="text-danger"><strong>Are you sure?</strong></p>
          <form method="GET" action="{{route('admin.subscriptions.lists.send', $list)}}" disable-on-submit>
            @csrf
            <input type="hidden" name="subject">
            <button type="submit" class="btn btn-sm btn-block btn-danger">Yes, send the email to the entire list</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>