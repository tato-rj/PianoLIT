@component('components.modal', ['id' => 'add-modal', 'header' => 'New event'])
@slot('body')
<form method="POST" action="{{route('admin.metaverse.store')}}">
  @csrf
  @select(['bag' => 'default', 'name' => 'location_id', 'placeholder' => 'Location', 'options' => (new \App\Metaverse)->locations()])

  @input(['bag' => 'default', 'name' => 'theme', 'placeholder' => 'Theme', 'limit' => 255])

  <div class="form-row"> 
    @select(['bag' => 'default', 'name' => 'time', 'placeholder' => 'Time', 'options' => timeslots()->toArray(), 'grid' => 'col'])
    @select(['bag' => 'default', 'name' => 'duration', 'placeholder' => 'Duration', 'options' => (new \App\Metaverse)->durations(), 'grid' => 'col'])
  </div>

  <div class="form-row"> 
    @select(['bag' => 'default', 'name' => 'day', 'placeholder' => 'Day', 'options' => dayslist(), 'grid' => 'col'])
    @select(['bag' => 'default', 'name' => 'month', 'placeholder' => 'Month', 'options' => monthslist(), 'grid' => 'col'])
    @select(['bag' => 'default', 'name' => 'year', 'placeholder' => 'Year', 'options' => [now()->year, now()->copy()->addYear()->year], 'grid' => 'col'])
  </div>

  @submit(['label' => 'Create event', 'block' => true])
</form>
@endslot
@endcomponent