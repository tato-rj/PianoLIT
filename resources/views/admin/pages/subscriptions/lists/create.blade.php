@component('components.modal', ['id' => 'add-modal', 'header' => 'Create Freepick list', 'centered' => true, 'show' => $errors->has('description') ? true : null])
    @slot('body')
        <form method="POST" action="{{ route('admin.subscriptions.lists.store') }}" disable-on-submit>
            @csrf
            <input type="hidden" name="name" value="{{ \App\EmailList::FREE_PICK }}">
            <p>Send the weekly free pick to your subscribers.</p>
            @textarea(['bag' => 'default', 'id' => 'list-description', 'name' => 'description', 'label' => 'Description', 'rows' => 3, 'limit' => 2000, 'value' => 'Weekly emails showcasing our free pick, its tutorials and the story behind the music.'])
            <button class="btn btn-primary" type="submit">@icon('plus')Create list</button>
        </form>
    @endslot
@endcomponent
