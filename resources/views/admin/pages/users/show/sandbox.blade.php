@include('admin.pages.users.show.title', ['title' => 'Sandbox'])
<div class="row">
  @if($user->membership()->exists())
  <div class="col-lg-4 col-md-4 col-sm-8 col-8 p-3">
    <form method="POST" action="{{route('admin.memberships.destroy', $user->id)}}">
      @csrf
      @method('DELETE')
      <div class="bg-advanced p-4 rounded cursor-pointer sandbox-button">
        <p class="mb-2">
          <strong><i class="fas fa-credit-card mr-2"></i>Remove membership</strong>
        </p>
        <span><small>Remove membership and restart trial</small></span>
      </div>
    </form>
  </div>
  @else
  <div class="col-lg-4 col-md-4 col-sm-8 col-8 p-3">
    <form method="POST" action="{{route('api.memberships.store')}}">
      <input type="hidden" name="receipt_data" value="fake-receipt-data">
      <input type="hidden" name="password" value="fake-password">
      <input type="hidden" name="user_id" value="{{$user->id}}">
      <div class="bg-pastel p-4 rounded cursor-pointer sandbox-button">
        <p class="mb-2">
          <strong><i class="fas fa-credit-card mr-2"></i>Create membership</strong>
        </p>
        <span><small>Simulate a request for a new membership</small></span>
      </div>
    </form>
  </div>
  @endif
</div>