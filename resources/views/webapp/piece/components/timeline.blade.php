@if($timeline->isNotEmpty())
<section class="piece-timeline" aria-label="Historical timeline">
  <div class="piece-timeline-events">
    @foreach($timeline as $event)
      @php($left = $loop->index % 2 === 1)
      @include('webapp.piece.components.event')
    @endforeach
  </div>
</section>

@once
  @push('page-modals')
    @component('components.dialogs.modal', [
      'id' => 'timeline-summary-modal', 'titleId' => 'timeline-summary-title', 'centered' => true, 'scrollable' => true,
      'options' => ['header' => ['raw' => true]],
    ])
      @slot('header')<h2 id="timeline-summary-title" class="h5 text-break mb-0"></h2>@endslot
      @slot('body')<p id="timeline-summary-text" class="text-break mb-0"></p>@endslot
    @endcomponent
  @endpush
  @push('scripts')
    <script src="{{ mix('js/views/timeline-summary.js') }}"></script>
  @endpush
@endonce
@endif
