<div id="carousel-container" class="position-relative">
  <div id="find-match-carousel" class="carousel slide" data-interval="false">
    <div class="carousel-inner">
      @foreach(['experience', 'score', 'pieces', 'challenge', 'composers', 'mood'] as $view)
        @include('funnels.find-your-match.panels.'.$view, ['loop' => $loop])
      @endforeach
    </div>
  </div>
  <div class="text-center carousel-buttons">
    <div style="opacity: 0" id="remaining-alert" class="t-2 text-center text-red mb-2">
      Select <span class="font-weight-bold"></span> more
    </div>
    @button([
      'id' => 'carousel-control',
      'label' => 'NEXT',
      'styles' => [
        'shadow' => true,
        'size' => 'wide',
        'theme' => 'primary',
      ],
      'classes' => 'btn-sm-block',
      'attr' => 'next',
      'disabled' => true,
    ])

    @button([
      'id' => 'carousel-submit',
      'label' => 'FIND MY BEST MATCH',
      'attr' => 'style=display:none',
      'data' => ['url' => route('funnels.find-your-match.results')],
      'styles' => [
        'shadow' => true,
        'size' => 'wide',
        'theme' => 'primary',
      ],
      'disabled' => true
    ])
  </div>
</div>