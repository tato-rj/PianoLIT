<article class="piece-timeline-event {{$event['highlight'] ? 'piece-timeline-highlighted' : ''}} {{empty($event['image_url']) ? 'piece-timeline-no-image' : ''}}">
  <div class="piece-timeline-year">
    @if(!empty($event['event_date']))
    <time datetime="{{$event['event_date']}}">{{$event['year']}}<small>{{\Carbon\Carbon::parse($event['event_date'])->format('M j')}}</small></time>
    @else
    <time>{{$event['year']}}</time>
    @endif
  </div>
  @if(!empty($event['image_url']))
  <img class="piece-timeline-image" src="{{$event['image_url']}}" alt="" loading="lazy" onerror="this.closest('.piece-timeline-event').classList.add('piece-timeline-no-image'); this.remove();" width="160" height="112">
  @endif
  <div class="piece-timeline-copy">
    @if($event['highlight'])<span class="piece-timeline-label">This piece</span>@endif
    <h6 class="piece-timeline-title">{{$event['title']}}</h6>
    <p class="text-muted mb-0">{{$event['description']}}</p>
    @if(!empty($event['source_url']))
    <details class="piece-timeline-sources small text-muted mt-2">
      <summary>Sources &amp; credits</summary>
      <a href="{{$event['source_url']}}" target="_blank" rel="noopener noreferrer">Wikipedia</a>
      <span> · {{$event['attribution']}}</span>
      @if(!empty($event['image_url']) && !empty($event['image_source_url']))
      <div class="mt-1"><a href="{{$event['image_source_url']}}" target="_blank" rel="noopener noreferrer">Image</a>
      @if(!empty($event['image_credit'])) · {{$event['image_credit']}}@endif
      @if(!empty($event['image_license_url'])) · <a href="{{$event['image_license_url']}}" target="_blank" rel="noopener noreferrer">{{$event['image_license']}}</a>
      @elseif(!empty($event['image_license'])) · {{$event['image_license']}}@endif</div>
      @endif
    </details>
    @endif
  </div>
</article>
