<div class="modal fade" id="game-results" tabindex="-1" role="dialog">
  <div class="modal-dialog mb-6" role="document">
    <div class="modal-content border-0">
      <div class="modal-body text-center p-4">
        <div id="game-feedback" class="mb-4"></div>
        <button type="button" class="btn btn-teal btn-sm btn-wide" data-dismiss="modal"><strong>{{$button}}</strong></button>
      </div>
      <div class="modal-footer text-center">
        <div class="w-100">
          <p class="mb-1"><strong>Did you like this game?</strong></p>
          <p>Subscribe and we'll keep you in the loop about the new ones!</p>
          @include('components.form.subscription')
        </div>
      </div>
    </div>
  </div>
</div>