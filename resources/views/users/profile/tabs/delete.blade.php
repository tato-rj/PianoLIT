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

@component('components.modal', ['id' => 'delete-modal'])
@slot('header')
Are you sure?
@endslot

@slot('body')
<p>You are about to delete your account!</p>
@include('users.profile.tabs.membership-alert')
<p class="text-danger m-0">This action cannot be undone</p>
@endslot

@slot('footer')
<form method="POST" action="{{route('users.destroy', auth()->user()->id)}}">
  @csrf
  @method('DELETE')
  <button type="submit" class="btn btn-sm btn-block btn-danger">Yes, I am sure</button>
</form>
@endslot
@endcomponent
