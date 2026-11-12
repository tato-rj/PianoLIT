@extends('layouts.app', [
	'raw' => true,
	'title' => 'PianoLIT Clips'
])

@push('header')
<link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css" />

<style>
.clip-page__player { background: #000; min-width: 0; }
.clip-page__webapp { min-height: 44px; }

@media (min-width: 768px) {
  .clip-page {
    display: grid;
    grid-template-rows: minmax(0, 1fr) auto;
    height: 100vh;
    height: 100dvh;
    min-height: 20rem;
  }

  .clip-page__player .plyr--video { height: 100%; }
}
</style>
@endpush

@section('content')
<div class="clip-page">
  <div class="clip-page__player">
    @video([
      'classes' => 'w-100 h-100',
      'id' => 'clip',
      'position' => $position,
      'url' => $url])
  </div>
  <nav aria-label="PianoLIT apps" class="d-flex justify-content-center align-items-center flex-wrap gap-2 gap-sm-3 px-3 py-3 bg-white">
    <a href="{{ route('webapp.discover') }}" target="_blank" rel="noopener" class="clip-page__webapp btn btn-outline-dark d-inline-flex align-items-center gap-2 px-3 py-2">
      @icon('pianolit', ['mr' => 0])<span>Open the web app</span>
    </a>
    @cta(['type' => 'ios', 'width' => 150, 'linkClasses' => 'd-inline-flex'])
  </nav>
</div>
@endsection

@push('scripts')
<script src="https://cdn.plyr.io/3.7.8/plyr.js"></script>
<script type="text/javascript">

  let position = $('#clip').data('position');
  let player = new Plyr('#clip');
  let positionSet = false;

player.on('canplay', event => {
  if (! positionSet) {
    player.currentTime = position;
    console.log(player.currentTime);
    positionSet = true;
  }
});

</script>
@endpush
