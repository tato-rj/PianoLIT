@foreach($candidates as $candidate)
<div class="col-lg-4 col-md-6 mb-3">
  <article class="card h-100" data-candidate="{{$candidate['source_id']}}">
    @if($candidate['image_url'])
    <img src="{{$candidate['image_url']}}" alt="" loading="lazy" onerror="this.remove();" class="timeline-candidate-image rounded-top">
    @endif
    <div class="card-body d-flex flex-column">
      <p class="text-brand mb-1">{{$candidate['event_date'] ?: $candidate['year']}}</p>
      <h6>{{$candidate['title']}}</h6>
      <p class="text-muted">{{$candidate['description']}}</p>
      <p class="small"><a href="{{$candidate['source_url']}}" target="_blank" rel="noopener noreferrer">Wikipedia source</a>
      @if($candidate['image_source_url']) · <a href="{{$candidate['image_source_url']}}" target="_blank" rel="noopener noreferrer">Image · {{$candidate['image_license']}}</a>@endif</p>
      <button type="button" class="btn btn-default mt-auto timeline-save">Save event</button>
      <span class="small mt-2 timeline-save-status" role="status"></span>
    </div>
  </article>
</div>
@endforeach
