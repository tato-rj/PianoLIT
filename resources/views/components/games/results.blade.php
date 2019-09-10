<div class="modal fade" id="game-results" tabindex="-1" role="dialog">
  <div class="modal-dialog mb-6" role="document">
    <div class="modal-content border-0">
      <div class="modal-body text-center p-4">
        <div id="game-feedback" class="mb-4">
          <p>You scored</p>
          <h1 class="font-weight-bold"></h1>
          <p>out of <span class="font-weight-bold"></span></p>
        </div>
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