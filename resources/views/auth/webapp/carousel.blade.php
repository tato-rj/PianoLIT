<div id="onboarding" class="carousel slide py-4" data-bs-ride="carousel">
  <div class="carousel-indicators">
    <button type="button" data-bs-target="#onboarding" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Show welcome slide"></button>
    <button type="button" data-bs-target="#onboarding" data-bs-slide-to="1" aria-label="Show music discovery slide"></button>
    <button type="button" data-bs-target="#onboarding" data-bs-slide-to="2" aria-label="Show piece details slide"></button>
  </div>
  <div class="carousel-inner">
    <div class="carousel-item active">
      <div class="text-center px-2">
          <h3>Welcome to PianoLIT</h3>
          <p class="text-muted mb-1" style="font-size: 110%">Browse piano pieces, scores, videos, and recordings.</p>
      </div>
      @include('auth.webapp.mockup', ['slide' => 1, 'description' => 'PianoLIT Discover, the free-pick Sonatina HWV 584 by Handel and Collections on desktop, tablet and phone'])
    </div>
    <div class="carousel-item">
      <div class="text-center px-2">
          <h3>Search piano pieces</h3>
          <p class="text-muted mb-1" style="font-size: 110%">Search by level, mood, style, and technique.</p>
      </div>
      @include('auth.webapp.mockup', ['slide' => 2, 'description' => 'PianoLIT Collections and the Melancholic mood collection on laptop and phone'])
    </div>
    <div class="carousel-item">
      <div class="text-center px-2">
          <h3>Scores, videos, and recordings</h3>
          <p class="text-muted mb-1" style="font-size: 110%">Read about each piece and use its available practice resources.</p>
      </div>
      @include('auth.webapp.mockup', ['slide' => 3, 'description' => 'PianoLIT performance player and score viewer for the free-pick Sonatina HWV 584 by Handel'])
    </div>
  </div>
</div>
