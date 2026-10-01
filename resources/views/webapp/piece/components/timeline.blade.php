<section class="piece-timeline" aria-label="Historical timeline">
  @forelse($timeline as $event)
    @include('webapp.piece.components.event')
  @empty
    <p class="text-muted mb-0">Historical events have not been added to this timeline yet.</p>
  @endforelse
</section>
