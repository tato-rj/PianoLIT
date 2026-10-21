<nav class="navbar navbar-expand-lg navbar-light py-5">
  <a class="navbar-brand" href="{{route('home')}}">
      @brandIcon
  </a>
  <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-items">
    <div class="animated-icon2"><span></span><span></span><span></span><span></span></div>
  </button>

  <div class="collapse navbar-collapse" id="navbar-items">
    <ul class="navbar-nav ms-auto">

      @include('layouts.menu.dropdown', [
        'label' => 'Tools',
        'groups' => [
          [
            'title' => 'Theory',
            'links' => ['Circle of Fifths' => route('tools.circle-of-fifths'), 'Chord Finder' => route('tools.chord-finder.index')]
          ],
          [
            'title' => 'Technique',
            'links' => ['Scales' => route('tools.scales.index'), 'Arpeggios' => route('tools.arpeggios.index')]
          ]
        ]])

      @include('layouts.menu.dropdown', [
        'label' => 'Resources',
        'groups' => [
          [
            'title' => 'Learn',
            'links' => [
              'Crash Courses' => route('crashcourses.index'),
              'Infographics' => route('resources.infographs.index'),
              'Quizzes' => route('quizzes.index')]
          ],
          [
            'title' => 'Listen',
            'links' => ['Great Pianists' => route('resources.pianists.index')]
          ],
        ]])

      @include('layouts.menu.dropdown', [
        'label' => 'Shop',
        'groups' => [
          [
            'links' => [
              'eBooks' => route('ebooks.index'),
              'eScores' => route('escores.index')]
          ],
        ]])

      @include('layouts.menu.link', ['label' => 'Blog', 'url' => route('posts.index')])

      @auth

        @include('layouts.menu.user')

      @else

        @include('layouts.menu.link', ['label' => 'Log in', 'url' => route('login')])

      @endauth

      {{-- @include('layouts.menu.search') --}}

    </ul>
  </div>
</nav>
