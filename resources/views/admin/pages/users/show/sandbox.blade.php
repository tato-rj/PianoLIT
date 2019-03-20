<div class="tab-pane fade {{request('section') == 'sandbox' ? 'show active' : null}} m-3" id="sandbox">
  <div class="row">

    @if($user->subscription()->exists())
    <div class="col-lg-4 col-md-4 col-sm-8 col-8 p-3">
      <form method="POST" action="{{route('piano-lit.users.subscription.destroy', $user->id)}}">
        {{csrf_field()}}
        {{method_field('DELETE')}}
        <div class="bg-advanced p-4 rounded cursor-pointer sandbox-button">
          <p class="mb-2">
            <strong><i class="fas fa-credit-card mr-2"></i>Remove subscription</strong>
          </p>
          <span><small>Remove subscription and restart trial</small></span>
        </div>
      </form>
    </div>
    @else
    <div class="col-lg-4 col-md-4 col-sm-8 col-8 p-3">
      <form method="POST" action="{{route('piano-lit.api.subscription.create')}}">
        <input type="hidden" name="receipt_data" value="fake-receipt-data">
        <input type="hidden" name="password" value="fake-password">
        <input type="hidden" name="user_id" value="{{$user->id}}">
        <div class="bg-pastel p-4 rounded cursor-pointer sandbox-button">
          <p class="mb-2">
            <strong><i class="fas fa-credit-card mr-2"></i>Create subscription</strong>
          </p>
          <span><small>Simulate a request for a new subscription</small></span>
        </div>
      </form>
    </div>
    @endif
  </div>
</div>