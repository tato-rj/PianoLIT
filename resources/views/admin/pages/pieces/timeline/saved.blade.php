<article class="card mb-3" data-event-id="{{$event->id}}" data-sort="{{sprintf('%04d', $event->year)}}-{{$event->event_date ?: '01-01'}}">
  <div class="card-body">
    <div class="d-flex align-items-start mb-3">
      @if($event->image_url)
      <img src="{{$event->image_url}}" alt="" loading="lazy" onerror="this.remove();" class="timeline-thumbnail rounded me-3">
      @endif
      <div><h6>{{$event->year}} · {{$event->title}}</h6>
        <a href="{{$event->source_url}}" target="_blank" rel="noopener noreferrer" class="small">Wikipedia source</a>
        <p class="small text-muted mb-0">{{$event->attribution}}</p>
      </div>
    </div>
    <form method="POST" action="{{route('admin.pieces.timeline.update', [$piece, $event])}}">
      @csrf
      @method('PATCH')
      <div class="row g-3 mb-3">
        <div class="col-sm-3"><label for="event-year-{{$event->id}}">Year</label><input id="event-year-{{$event->id}}" name="year" type="number" required min="1" max="9999" class="form-control" value="{{$event->year}}"></div>
        <div class="col-sm-3"><label for="event-date-{{$event->id}}">Exact date (optional)</label><input id="event-date-{{$event->id}}" name="event_date" type="date" class="form-control" value="{{$event->event_date}}"></div>
        <div class="col-sm-6"><label for="event-title-{{$event->id}}">Title</label><input id="event-title-{{$event->id}}" name="title" required maxlength="255" class="form-control" value="{{$event->title}}"></div>
      </div>
      <div class="form-group"><label for="event-description-{{$event->id}}">Short description</label><textarea id="event-description-{{$event->id}}" name="description" required maxlength="2000" rows="3" class="form-control">{{$event->description}}</textarea></div>
      <details class="mb-3"><summary class="text-brand cursor-pointer">Image and attribution</summary>
        <p class="small text-muted mt-2 mb-0">When replacing an image, update its source, credit and license below.</p>
        @foreach(['image_url' => 'Image URL (HTTPS; leave blank for no image)', 'image_source_url' => 'Image source URL', 'image_credit' => 'Image credit', 'image_license' => 'Image license', 'image_license_url' => 'License URL'] as $field => $label)
        <div class="form-group mt-2"><label for="{{$field}}-{{$event->id}}">{{$label}}</label><input id="{{$field}}-{{$event->id}}" name="{{$field}}" type="{{$field === 'image_credit' || $field === 'image_license' ? 'text' : 'url'}}" class="form-control" value="{{$event->$field}}"></div>
        @endforeach
      </details>
      <button type="submit" class="btn btn-default btn-sm">Update event</button>
    </form>
    <form method="POST" action="{{route('admin.pieces.timeline.destroy', [$piece, $event])}}" class="mt-2">
      @csrf
      @method('DELETE')
      <button type="submit" class="btn btn-outline-danger btn-sm">Remove event</button>
    </form>
  </div>
</article>
