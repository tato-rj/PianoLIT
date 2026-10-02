<div class="tab-pane fade" id="tab-timeline">
  @if((! $piece->composed_in && ! $piece->published_in) && $piece->timelineEvents()->exists())
  <p class="text-center px-5">We do not know when this piece was composed or published, so these are some events that took place during the lifetime {{$piece->composer->name}}.</p>
  @endif
  @include('webapp.piece.components.timeline')
</div>
