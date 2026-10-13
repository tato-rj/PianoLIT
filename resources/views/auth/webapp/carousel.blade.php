<div id="onboarding" class="carousel slide py-4" data-bs-ride="carousel">
  <ol class="carousel-indicators list-style-none">
    <li data-bs-target="#onboarding" data-bs-slide-to="0" class="active"></li>
    <li data-bs-target="#onboarding" data-bs-slide-to="1"></li>
    <li data-bs-target="#onboarding" data-bs-slide-to="2"></li>
  </ol>
  <div class="carousel-inner">
    <div class="carousel-item active">
      <div class="text-center px-2">
          <h3>Welcome to PianoLIT</h3>
          <p class="text-muted mb-1" style="font-size: 110%">We're here to help you find new and exciting pieces to play.</p>
      </div>
      @include('auth.webapp.mockup', ['slide' => 1, 'description' => 'PianoLIT Discover, piece details and Collections on desktop, tablet and phone'])
    </div>
    <div class="carousel-item">
      <div class="text-center px-2">
          <h3>Discover</h3>
          <p class="text-muted mb-1" style="font-size: 110%">Find new repertoire based on mood, categories, techniques and more.</p>
      </div>
      @include('auth.webapp.mockup', ['slide' => 2, 'description' => 'PianoLIT Collections and the Melancholic mood collection on laptop and phone'])
    </div>
    <div class="carousel-item">
      <div class="text-center px-2">
          <h3>Learn</h3>
          <p class="text-muted mb-1" style="font-size: 110%">Expand your musical knowledge and become a better pianist!</p>
      </div>
      @include('auth.webapp.mockup', ['slide' => 3, 'description' => 'PianoLIT performance player and score viewer for A Sad Song by Yuri Levitin'])
    </div>
  </div>
</div>
