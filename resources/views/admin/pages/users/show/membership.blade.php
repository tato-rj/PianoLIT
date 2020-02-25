@include('admin.pages.users.show.title', ['title' => 'Membership'])

<div class="row">
	@include('admin.pages.users.show.status.'.$user->getStatus())
</div>