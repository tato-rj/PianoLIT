<div class="modal" id="modal-error" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-body text-center">
        <img src="{{asset('images/misc/confused.svg')}}" class="m-4" style="width: 80px">
        <p class="mb-2 text-teal"><strong>Hm, something went wrong...</strong></p>
        <div class="font-weight-bld mb-2 bg-light rounded px-3 pt-1 pb-3">
          <div class="mb-1 text-muted"><small>Here's what happened</small></div>
          <div id="error-report"></div>
        </div>
        <p>Give it another try!</p>
        <button type="button" class="btn btn-primary btn-sm btn-wide mb-3" data-dismiss="modal" aria-label="Close">Try again</button>
      </div>
    </div>
  </div>
</div>