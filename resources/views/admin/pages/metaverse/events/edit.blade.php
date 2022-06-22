@component('components.modal', ['id' => 'edit-'.$event->id.'-modal', 'header' => 'Update event'])
@slot('body')
<form method="POST" action="{{route('admin.metaverse.event.update', $event)}}">
  @csrf
  @method('PATCH')
  @select(['bag' => 'default', 'name' => 'location_id', 'placeholder' => 'Location', 'options' => $item->locations(), 'select' => $item->location_id])
  @input(['bag' => 'default', 'name' => 'theme', 'placeholder' => 'Theme', 'limit' => 255, 'value' => $item->theme])

  @textarea(['bag' => 'default', 'name' => 'description', 'placeholder' => 'Description (optional)', 'rows' => 4, 'required' => false, 'value' => $item->description])

  <div class="form-row"> 
    @select(['bag' => 'default', 'name' => 'time', 'placeholder' => 'Time', 'options' => timeslots()->toArray(), 'grid' => 'col', 'select' => $item->time])
    @select(['bag' => 'default', 'name' => 'duration', 'placeholder' => 'Duration', 'options' => (new \App\Metaverse\MetaverseEvent)->durations(), 'grid' => 'col', 'select' => $item->duration])
  </div>

  <div class="form-row"> 
    @select(['bag' => 'default', 'name' => 'day', 'placeholder' => 'Day', 'options' => dayslist(), 'grid' => 'col', 'select' => $item->date->day])
    @select(['bag' => 'default', 'name' => 'month', 'placeholder' => 'Month', 'options' => monthslist(), 'grid' => 'col', 'select' => monthslist($item->date->month)])
    @select(['bag' => 'default', 'name' => 'year', 'placeholder' => 'Year', 'options' => [now()->year, now()->copy()->addYear()->year], 'grid' => 'col', 'select' => $item->date->year])
  </div>

  @submit(['label' => 'Update event', 'block' => true])
</form>
@endslot
@endcomponent