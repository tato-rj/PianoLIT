@component('components.modal', ['id' => 'add-modal', 'header' => 'New infographic'])
@slot('body')
<form method="POST" action="{{route('admin.infographs.store')}}" enctype="multipart/form-data">
	@csrf

	@input(['bag' => 'default', 'name' => 'name', 'placeholder' => 'Infographic name'])
    @file(['bag' => 'default', 'name' => 'cover_image', 'placeholder' => 'Cover image'])
    @options(['bag' => 'default', 'type' => 'checkbox', 'name' => 'topics', 'label' => 'Topics', 'options' => $topics->pluck('id', 'name'), 'required' => false])
	@textarea(['bag' => 'default', 'name' => 'description', 'placeholder' => 'Description', 'limit' => 238, 'rows' => 3])

  	@submit(['label' => 'Create infographic', 'block' => true])

	<input type="hidden" name="width">
	<input type="hidden" name="height">
</form>
@endslot
@endcomponent
