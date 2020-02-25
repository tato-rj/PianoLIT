@include('admin.pages.users.show.title', ['title' => 'Membership'])

<div class="row">
	<p>{{$user->getStatus()}}</p>
	@include('admin.pages.users.show.status.'.$user->getStatus())
</div>