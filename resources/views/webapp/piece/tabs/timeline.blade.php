<div class="tab-pane fade" id="tab-timeline">
  @if((! $piece->composed_in && ! $piece->published_in) && $piece->timelineEvents()->exists())
  <p class="text-center px-5">We do not know the year this piece was composed or published, so these are some events happening during the lifetime of {{$piece->composer->name}}.</p>
  @endif
  @include('webapp.piece.components.timeline')
</div>
