@component('components.modal', ['id' => 'add-modal', 'header' => 'New clip'])
@slot('body')
<form method="POST" action="{{route('admin.clips.store')}}">
	@csrf
	@input(['bag' => 'default', 'name' => 'name', 'placeholder' => 'Name', 'limit' => 120])
	@input(['bag' => 'default', 'name' => 'url', 'placeholder' => 'URL', 'limit' => 220])

	@submit(['label' => 'Create clip', 'block' => true])
</form>
@endslot
@endcomponent