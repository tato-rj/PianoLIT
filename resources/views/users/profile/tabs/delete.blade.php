<div class="tab-pane fade" id="list-delete" role="tabpanel" aria-labelledby="list-delete-list">
	<div>
		<h5>Delete my account</h5>
		<p>If you wish to delete your account, just follow the steps below.</p>

    @include('users.profile.tabs.membership-alert')

		<p class="text-danger mb-4"><u>Important</u>: This action cannot be undone.</p>
		<a href="" data-name="{{auth()->user()->full_name}}" data-url="{{route('users.destroy', auth()->user()->id)}}" data-toggle="modal" data-target="#delete-modal" class="btn btn-wide btn-danger">
			@fa(['icon' => 'trash-alt'])I want to permanently delete my account
		</a>
	</div>
</div>

<div class="modal fade" id="delete-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content border-0">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Are you sure?</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p>You are about to delete your account!</p>

        @include('users.profile.tabs.membership-alert')

        <p class="text-danger m-0">This action cannot be undone</p>
      </div>
      <div class="modal-footer">
        <form method="POST" action="{{route('users.destroy', auth()->user()->id)}}">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-sm btn-block btn-danger">Yes, I am sure</button>
        </form>
      </div>
    </div>
  </div>
</div>