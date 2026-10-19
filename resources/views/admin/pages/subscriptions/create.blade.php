@component('components.modal', ['id' => 'add-modal', 'header' => 'Add emails', 'show' => $errors->has('emails') || $errors->has('origin_url') ? true : null])
@slot('body')
<form method="POST" action="{{route('admin.subscriptions.store')}}">
  @csrf

  @textarea(['bag' => 'default', 'id' => 'subscriber-emails', 'name' => 'emails', 'label' => 'Email addresses', 'textImprover' => false, 'placeholder' => 'email1@example.com, email2@example.com', 'rows' => 3])

  @input(['bag' => 'default', 'id' => 'subscriber-origin', 'name' => 'origin_url', 'label' => 'Origin', 'limit' => 255, 'placeholder' => 'Where are these emails from?'])

  @submit(['label' => 'Add subscribers', 'block' => true])
</form>
@endslot
@endcomponent
