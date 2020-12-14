@component('components.modal', ['id' => 'add-modal', 'header' => 'New event'])
@slot('body')
<form method="POST" action="{{route('admin.timelines.store')}}">
  @csrf
  @input(['bag' => 'default', 'type' => 'number', 'name' => 'year', 'placeholder' => 'Year', 'min' => 1600, 'max' => now()->year])
  @select(['bag' => 'default', 'name' => 'type', 'placeholder' => 'Type', 'options' => [
    'World history' => 'history',
    'US history' => 'us-history',
    'Science' => 'science',
    'Literature' => 'literature',
    'Music' => 'music',
    'Premiere' => 'premiere',
    'Birthday' => 'birthday',
    'Deathday' => 'deathday'
  ]])
  @input(['bag' => 'default', 'name' => 'event', 'placeholder' => 'New event here...'])
  @input(['bag' => 'default', 'name' => 'url', 'placeholder' => 'URL address'])

  @submit(['label' => 'Add event', 'block' => true])
</form>
@endslot
@endcomponent
