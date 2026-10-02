<article class="card timeline-saved-event" data-event-id="{{$event->id}}" data-sort="{{sprintf('%04d', $event->year)}}-{{$event->event_date ?: '01-01'}}">
  <details class="timeline-event-editor">
    <summary class="timeline-event-summary">
      <span class="timeline-event-year text-brand" @if($event->event_date) title="{{$event->event_date}}" @endif>{{$event->year}}
        @if($event->event_date)<small class="d-block text-muted">{{\Carbon\Carbon::parse($event->event_date)->format('M j')}}</small>@endif
      </span>
      @if($event->image_url)
      <img src="{{$event->image_url}}" alt="" loading="lazy" onerror="this.remove();" class="timeline-thumbnail">
      @endif
      <span class="timeline-event-overview">
        <span class="timeline-event-title">{{$event->title}}</span>
        <span class="timeline-event-description small text-muted">{{$event->description}}</span>
      </span>
      <span class="timeline-edit-label text-brand small">Edit @icon('chevron-down', ['mr' => 0])</span>
    </summary>
    <div class="timeline-event-fields">
      <form id="timeline-update-{{$event->id}}" method="POST" action="{{!empty($isLibrary) ? route('admin.timeline-events.update', $event) : route('admin.pieces.timeline.update', [$piece, $event])}}">
        @csrf
        @method('PATCH')
        <div class="timeline-event-inputs">
          <div><label for="event-year-{{$event->id}}">Year</label><input id="event-year-{{$event->id}}" name="year" type="number" required min="1" max="9999" class="form-control form-control-sm" value="{{$event->year}}"></div>
          <div><label for="event-date-{{$event->id}}">Exact date (optional)</label><input id="event-date-{{$event->id}}" name="event_date" type="date" class="form-control form-control-sm" value="{{$event->event_date}}"></div>
          <div class="timeline-title-field"><label for="event-title-{{$event->id}}">Title</label><input id="event-title-{{$event->id}}" name="title" required maxlength="255" class="form-control form-control-sm" value="{{$event->title}}"></div>
        </div>
        <div class="mb-2"><label for="event-description-{{$event->id}}">Short description</label><textarea id="event-description-{{$event->id}}" name="description" required maxlength="2000" rows="2" class="form-control form-control-sm">{{$event->description}}</textarea></div>
        <details class="timeline-image-fields mb-3"><summary class="text-brand small">Image and attribution</summary>
          <p class="small text-muted mt-2 mb-2">When replacing an image, update its source, credit and license below.</p>
          <div class="timeline-image-inputs">
            @foreach(['image_url' => 'Image URL (HTTPS; blank for no image)', 'image_source_url' => 'Image source URL', 'image_credit' => 'Image credit', 'image_license' => 'Image license', 'image_license_url' => 'License URL'] as $field => $label)
            <div><label for="{{$field}}-{{$event->id}}">{{$label}}</label><input id="{{$field}}-{{$event->id}}" name="{{$field}}" type="{{$field === 'image_credit' || $field === 'image_license' ? 'text' : 'url'}}" class="form-control form-control-sm" value="{{$event->$field}}"></div>
            @endforeach
          </div>
          <p class="small text-muted mt-2 mb-0">{{$event->attribution}}</p>
        </details>
      </form>
      <div class="timeline-event-actions">
        <button type="submit" form="timeline-update-{{$event->id}}" class="btn btn-default btn-sm">Update event</button>
        <a href="{{$event->source_url}}" target="_blank" rel="noopener noreferrer" class="small">Wikipedia source</a>
        <form method="POST" action="{{!empty($isLibrary) ? route('admin.timeline-events.destroy', $event) : route('admin.pieces.timeline.destroy', [$piece, $event])}}" class="ms-auto timeline-remove">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-outline-danger btn-sm">Remove event</button>
          <span class="small text-danger d-block timeline-remove-status" role="status"></span>
        </form>
      </div>
    </div>
  </details>
</article>
