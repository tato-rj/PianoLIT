<nav class="navbar navbar-expand-lg navbar-light py-5">
  <a class="navbar-brand" href="{{config('app.url')}}">
      <img src="{{asset('images/brand/app-icon.svg')}}" style="border-radius: 20%; width: 60px">
  </a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>

  <div class="collapse navbar-collapse" id="navbarSupportedContent">
    <ul class="navbar-nav ml-auto">

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
          ],
          [
            'title' => 'For teachers', 
            'links' => ['Studio Policy Generator' => route('tools.studio-policies'), 'Staff Generator' => route('tools.staff')]
          ],
        ]])

      @include('layouts.menu.dropdown', [
        'label' => 'Resources',
        'groups' => [
          [
            'title' => 'Learn', 
            'links' => ['Infographics' => route('resources.infographs.index'), 'Music Timeline' => route('resources.timeline')]
          ],
          [
            'title' => 'Listen', 
            'links' => ['Great Pianists' => route('resources.pianists.index')]
          ],
          [
            'title' => 'Games', 
            'links' => ['Quizzes' => route('quizzes.index'), 'True or False' => route('true-or-false.index'), 'Riddles' => route('riddles')]
          ],
        ]])
  
      @env('local')
      @include('layouts.menu.dropdown', [
        'label' => 'Shop',
        'groups' => [
          [
            'links' => ['eBooks' => route('ebooks.index')]
          ],
          [
            'links' => ['eScores' => route('escores.index')]
          ],
        ]])

      {{-- @include('layouts.menu.link', ['label' => 'eBooks', 'url' => route('ebooks.index')]) --}}
      @endenv

      @include('layouts.menu.link', ['label' => 'Blog', 'url' => route('posts.index')])

      @auth

        @include('layouts.menu.user')

      @else

        @include('layouts.menu.link', ['label' => 'Log in', 'url' => route('login')])

      @endauth

      @include('layouts.menu.search')

    </ul>
  </div>
</nav>