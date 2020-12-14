@component('components.modal', ['id' => 'add-modal', 'header' => 'Add emails'])
@slot('body')
<form method="POST" action="{{route('admin.subscriptions.store')}}">
  @csrf

  @textarea(['bag' => 'default', 'name' => 'emails', 'placeholder' => 'Separate the emails with comma and a space, like this: email1@example.com, email2@example.com, email3@example.com, etc...', 'rows' => 3])

  @input(['bag' => 'default', 'name' => 'origin_url', 'placeholder' => 'Where are these emails from?'])

  @submit(['label' => 'Add subscribers', 'block' => true])
</form>
@endslot
@endcomponent
