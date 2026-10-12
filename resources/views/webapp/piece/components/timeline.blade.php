@if($timeline->isNotEmpty())
<section class="piece-timeline" aria-label="Historical timeline">
  <div class="piece-timeline-events mb-5">
    @foreach($timeline as $event)
      @php($left = $loop->index % 2 === 1)
      @include('webapp.piece.components.event')
    @endforeach
  </div>
</section>
@endif
