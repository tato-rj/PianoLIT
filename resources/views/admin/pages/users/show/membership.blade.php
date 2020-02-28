@include('admin.pages.users.show.title', ['title' => 'Membership', 'icon' => 'credit-card'])

<div class="row mb-4">
	@include('admin.pages.users.show.status.'.$user->getStatus())
</div>