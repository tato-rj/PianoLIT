@if($timeline->isNotEmpty())
<section class="piece-timeline" aria-label="Historical timeline">
  <p class="piece-timeline-heading text-muted">{{$piece->composer->name}}'s world <span>{{$timelinePeriod['start_year']}}–{{$timelinePeriod['end_year']}}</span></p>
  <div class="piece-timeline-events">
    @foreach($timeline as $event)
      @php($left = $loop->index % 2 === 1)
      @include('webapp.piece.components.event')
    @endforeach
  </div>
</section>
@endif
