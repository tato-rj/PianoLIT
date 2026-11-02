@component('admin.components.editor.section', ['id' => 'piece-description', 'title' => 'Description'])
    @slot('actions')
        <button type="button" class="btn btn-sm btn-dark" data-sample-description>@icon('wand-sparkles')Use a sample</button>
    @endslot
    @textarea(['bag' => 'default', 'id' => 'piece-description-input', 'name' => 'description', 'label' => 'About this piece', 'value' => $piece->description, 'rows' => 5, 'required' => false, 'placeholder' => 'Enter a description'])
    <div data-description-error hidden>
        @alert(['color' => 'red', 'message' => 'The sample could not be loaded. Try again.'])
    </div>
@endcomponent
