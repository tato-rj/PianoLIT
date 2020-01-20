<div class="modal fade" id="emails-send-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Send email list</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="mb-4">
          <form method="GET" id="send-to-url" class="mb-2">
            @csrf
            @input([
              'label' => 'Send a preview email to', 
              'value' => null, 
              'name' => 'email', 
              'bag' => 'default', 
              'asterisk' => true])
            <button type="submit" class="btn btn-sm btn-block btn-default">Send preview</button>
          </form>

          <a href="" id="preview-url" target="_blank" class="btn btn-outline-secondary btn-block btn-sm px-3 mr-2">Or just preview in the browser</a>
        </div>
        <div class="bg-light px-3 py-2">
          By clicking the button below you will send this email to the entire list.
          <p class="text-danger"><strong>Are you sure?</strong></p>
          <form method="GET" id="send-url">
            @csrf
            <button type="submit" class="btn btn-sm btn-block btn-danger">Yes, send the email to the entire list</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>