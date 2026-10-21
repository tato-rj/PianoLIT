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
          <p class="text-muted mb-1" style="font-size: 110%">We're here to help you find new and exciting pieces to play.</p>
      </div>
      @include('auth.webapp.mockup', ['slide' => 1, 'description' => 'PianoLIT Discover, the free-pick Sonatina HWV 584 by Handel and Collections on desktop, tablet and phone'])
    </div>
    <div class="carousel-item">
      <div class="text-center px-2">
          <h3>Find music that inspires you</h3>
          <p class="text-muted mb-1" style="font-size: 110%">Explore pieces by mood, style, and technique, and discover something new to play.</p>
      </div>
      @include('auth.webapp.mockup', ['slide' => 2, 'description' => 'PianoLIT Collections and the Melancholic mood collection on laptop and phone'])
    </div>
    <div class="carousel-item">
      <div class="text-center px-2">
          <h3>Go beyond the notes</h3>
          <p class="text-muted mb-1" style="font-size: 110%">Discover the stories, details, and performances that bring each piece to life.</p>
      </div>
      @include('auth.webapp.mockup', ['slide' => 3, 'description' => 'PianoLIT performance player and score viewer for the free-pick Sonatina HWV 584 by Handel'])
    </div>
  </div>
</div>
