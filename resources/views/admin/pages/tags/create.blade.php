@component('components.modal', ['id' => 'add-modal', 'header' => 'New topic'])
@slot('body')
<form method="POST" action="{{route('admin.tags.store')}}">
	@csrf
	@input(['bag' => 'default', 'name' => 'name', 'placeholder' => 'Create a new tag here'])
    @select(['bag' => 'default', 'name' => 'type', 'placeholder' => 'Type', 'optGroups' => \App\Tag::labels()])
	@submit(['label' => 'Create tag', 'block' => true])
</form>
@endslot
@endcomponent