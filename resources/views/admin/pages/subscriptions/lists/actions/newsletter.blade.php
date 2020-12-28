@component('admin.pages.subscriptions.lists.actions.action', ['list' => $list])
@slot('before')
  <div class="alert-grey px-3 py-2 rounded mb-4">
    @input([
      'label' => 'Newsletter subject', 
      'value' => null, 
      'name' => 'subject-input',
      'placeholder' => 'PianoLIT Newsletter',
      'bag' => 'default'])
  </div>
@endslot
@endcomponent