@include('admin.pages.users.show.title', ['title' => 'Membership', 'icon' => 'credit-card'])

<div class="row">
	@include('admin.pages.users.show.status.'.$user->getStatus())
</div>