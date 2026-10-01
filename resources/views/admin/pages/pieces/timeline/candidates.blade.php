@foreach($candidates as $candidate)
<article class="card timeline-candidate" data-candidate="{{$candidate['source_id']}}" data-year="{{$candidate['year']}}" data-date="{{$candidate['event_date'] ?: sprintf('%04d-01-01', $candidate['year'])}}">
  @if($candidate['image_url'])
  <img src="{{$candidate['image_url']}}" alt="" loading="lazy" onerror="this.remove();" class="timeline-candidate-image">
  @endif
  <div class="timeline-candidate-copy">
    <span class="small text-brand">{{$candidate['event_date'] ?: $candidate['year']}}</span>
    <h6 class="mb-1">{{$candidate['title']}}</h6>
    <details class="timeline-candidate-description small text-muted mb-2">
      <summary>
        <span class="timeline-description-preview" aria-hidden="true">{{$candidate['description']}}</span>
        <span class="timeline-description-more text-brand">Read description</span>
        <span class="timeline-description-less text-brand">Hide description</span>
      </summary>
      <p class="mt-1 mb-0">{{$candidate['description']}}</p>
    </details>
    <div class="timeline-candidate-actions">
      <button type="button" class="btn btn-default btn-sm timeline-save">Save event</button>
      <span class="timeline-source-links small"><a href="{{$candidate['source_url']}}" target="_blank" rel="noopener noreferrer">Wikipedia</a>
      @if($candidate['image_source_url']) · <a href="{{$candidate['image_source_url']}}" target="_blank" rel="noopener noreferrer" title="{{$candidate['image_license']}}">Image credit</a>@endif</span>
    </div>
    <span class="small timeline-save-status" role="status"></span>
  </div>
</article>
@endforeach
