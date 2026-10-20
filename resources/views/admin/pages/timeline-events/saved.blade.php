@php
  $eventBag = 'timeline-event-'.$event->id;
  $eventHasErrors = $errors->getBag($eventBag)->any();
  $oldPrefix = $eventHasErrors ? '' : $eventBag.'.';
@endphp
<article class="timeline-saved-event" data-event-id="{{$event->id}}" data-year="{{$event->year}}" data-sort="{{sprintf('%04d', $event->year)}}-{{$event->event_date ?: '01-01'}}">
  <details class="timeline-event-editor" @if($eventHasErrors) open @endif>
    <summary class="timeline-event-summary">
      <span class="timeline-event-year" @if($event->event_date) title="{{$event->event_date}}" @endif>{{$event->year}}
        @if($event->event_date)<small class="d-block text-muted">{{\Carbon\Carbon::parse($event->event_date)->format('M j')}}</small>@endif
      </span>
      @if($event->image_url)
      <img src="{{$event->image_url}}" alt="" loading="lazy" onerror="this.remove();" class="timeline-thumbnail">
      @endif
      <span class="timeline-event-overview">
        <span class="timeline-event-title">{{$event->title}}</span>
        <span class="timeline-event-description small text-muted">{{$event->description}}</span>
      </span>
      <span class="timeline-edit-label btn btn-sm btn-warning text-dark">@icon('square-pen')Edit</span>
    </summary>
    <div class="timeline-event-fields">
      <form id="timeline-update-{{$event->id}}" method="POST" action="{{route('admin.timeline-events.update', $event)}}">
        @csrf
        @method('PATCH')
        <div class="timeline-event-inputs">
          @input(['bag' => $eventBag, 'id' => 'event-year-'.$event->id, 'name' => 'year', 'label' => 'Year', 'type' => 'number', 'min' => 1, 'limit' => 9999, 'value' => $event->year, 'oldInputKey' => $oldPrefix.'year'])
          @input(['bag' => $eventBag, 'id' => 'event-date-'.$event->id, 'name' => 'event_date', 'label' => 'Exact date (optional)', 'type' => 'date', 'value' => $event->event_date, 'required' => false, 'oldInputKey' => $oldPrefix.'event_date'])
          @input(['bag' => $eventBag, 'id' => 'event-title-'.$event->id, 'name' => 'title', 'label' => 'Title', 'limit' => 255, 'value' => $event->title, 'grid' => 'timeline-title-field', 'oldInputKey' => $oldPrefix.'title'])
        </div>
        @textarea(['bag' => $eventBag, 'id' => 'event-description-'.$event->id, 'name' => 'description', 'label' => 'Short description', 'value' => $event->description, 'rows' => 2, 'limit' => 2000, 'oldInputKey' => $oldPrefix.'description'])
        <details class="timeline-image-fields mb-3"><summary class="small text-muted">Image and attribution</summary>
          <p class="small text-muted mt-2 mb-3">When replacing an image, update its source, credit and license below.</p>
          <div class="timeline-image-inputs">
            @foreach(['image_url' => 'Image URL (HTTPS; blank for no image)', 'image_source_url' => 'Image source URL', 'image_credit' => 'Image credit', 'image_license' => 'Image license', 'image_license_url' => 'License URL'] as $field => $label)
              @input(['bag' => $eventBag, 'id' => $field.'-'.$event->id, 'name' => $field, 'label' => $label, 'type' => in_array($field, ['image_credit', 'image_license']) ? 'text' : 'url', 'value' => $event->$field, 'required' => false, 'limit' => $field === 'image_license' ? 255 : 2000, 'oldInputKey' => $oldPrefix.$field])
            @endforeach
          </div>
          <p class="small text-muted mb-0">{{ $event->attribution }}</p>
        </details>
      </form>
      <div class="timeline-event-actions">
        <button type="submit" form="timeline-update-{{$event->id}}" class="btn btn-primary btn-sm">@icon('check')Save changes</button>
        <a href="{{$event->source_url}}" target="_blank" rel="noopener noreferrer" class="btn btn-dark btn-sm">@icon('external-link')Wikipedia source</a>
        <form method="POST" action="{{route('admin.timeline-events.destroy', $event)}}" class="ms-auto timeline-remove">
          @csrf
          @method('DELETE')
          @include('admin.components.table-action', ['tableAction' => ['kind' => 'delete', 'type' => 'submit', 'label' => 'Remove event', 'iconOnly' => false]])
          <span class="small text-danger d-block timeline-remove-status" role="status"></span>
        </form>
      </div>
    </div>
  </details>
</article>
