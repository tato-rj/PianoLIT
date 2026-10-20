@component('components.modal', ['id' => 'add-modal', 'header' => 'Create a new list', 'centered' => true, 'show' => $errors->has('name') || $errors->has('description') || old('name') !== null ? true : null])
    @slot('body')
        <form method="POST" action="{{ route('admin.subscriptions.lists.store') }}" disable-on-submit>
            @csrf
            @input(['bag' => 'default', 'id' => 'list-name', 'name' => 'name', 'label' => 'List name', 'limit' => 100, 'placeholder' => 'e.g. Practice tips', 'autocomplete' => 'off'])
            @textarea(['bag' => 'default', 'id' => 'list-description', 'name' => 'description', 'label' => 'Description', 'rows' => 3, 'limit' => 255])
            <button class="btn btn-primary" type="submit">@icon('plus')Create list</button>
        </form>
    @endslot
@endcomponent
