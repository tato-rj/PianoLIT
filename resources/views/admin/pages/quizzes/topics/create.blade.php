@component('components.modal', ['id' => 'add-modal', 'header' => 'New topic'])
@slot('body')
<form method="POST" action="{{route('admin.quizzes.topics.store')}}">
	@csrf
	@input(['bag' => 'default', 'name' => 'name', 'placeholder' => 'Create a new topic here'])
	@submit(['label' => 'Create topic', 'block' => true])
</form>
@endslot
@endcomponent