<!-- Modal -->
<div class="modal fade" data-user_id="{{$user->id}}" data-url="{{route('api.memberships.history')}}" id="membership-history" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">{{$user->first_name}}'s receipts history</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="p-5 text-center" id="history-loading">
          <i class="fab fa-apple fa-2x mb-2"></i>
          <p class="m-0 text-muted">Hang on, we're calling Apple...</p>
        </div>
        <div id="history-data" style="display: none;">
          
        </div>
      </div>
    </div>
  </div>
</div>