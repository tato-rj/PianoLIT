@if($timeline->isNotEmpty())
<section class="piece-timeline" aria-label="Historical timeline">
  <p class="piece-timeline-heading text-muted">{{$piece->composer->name}}'s world <span>{{$timelinePeriod['start_year']}}–{{$timelinePeriod['end_year']}}</span></p>
  <div class="piece-timeline-events">
    @php($contextIndex = 0)
    @foreach($timeline as $event)
      @php($left = empty($event['composer_milestone']) && !$event['highlight'] && $contextIndex++ % 2 === 0)
      @include('webapp.piece.components.event')
    @endforeach
  </div>
</section>
@endif
