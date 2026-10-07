@component('components.fullscreen-modal', ['id' => 'match-tour-modal', 'headingId' => 'match-tour-heading', 'classes' => 'match-tour-modal', 'data' => ['tour-url' => route('webapp.tour'), 'auto-open' => !empty($autoOpen) ? 'true' : 'false']])
    @component('components.fullscreen-modal-header', ['headingId' => 'match-tour-heading', 'title' => 'Find your match', 'closeLabel' => 'Close Find your match'])
        @slot('headerContent')@include('webapp.tour.progress')@endslot
    @endcomponent
    <div class="match-ambience" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
    <div class="match-tour-body" data-tour-content></div>
@endcomponent
@once
@push('scripts')
<script src="{{ mix('js/views/match-tour.js') }}"></script>
<script>
const matchTourLauncher = new MatchTour.Launcher(document.getElementById('match-tour-modal'), axios);
if (document.getElementById('match-tour-modal').dataset.autoOpen === 'true') matchTourLauncher.open(document.querySelector('[data-match-tour-open]'));
</script>
@endpush
@endonce
