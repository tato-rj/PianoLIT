@extends('layouts.app', ['projectHome' => true, 'bodyClass' => 'project-home', 'title' => 'PianoLIT — A world of piano.'])

@section('content')
<div class="project-home__page">
    <header class="project-home__header">
        <a href="{{ route('home') }}" class="d-inline-flex" aria-label="PianoLIT home">
            @brandIcon(['size' => 'clamp(48px, 7vw, 64px)'])
        </a>
        <nav aria-label="Account and YouTube">
            <ul class="d-flex align-items-center gap-2 list-unstyled mb-0">
                <li class="nav-item">
                    <a href="https://www.youtube.com/pianolit" class="btn btn-secondary" target="_blank" rel="noopener noreferrer">YouTube</a>
                </li>
                @auth('web')
                    @include('layouts.menu.user')
                @else
                    <li class="nav-item">
                        <a href="{{ route('login') }}" class="btn btn-primary">Sign in</a>
                    </li>
                @endauth
            </ul>
        </nav>
    </header>

    <section class="project-home__hero" aria-labelledby="home-heading">
        <h1 id="home-heading">A world of piano.<br><span>Find your way in.</span></h1>
        <p>Discover, play, and fall in love with music.</p>
        <p class="project-home__invitation" id="projects-heading">Find below our projects <span aria-hidden="true">↓</span></p>
    </section>

    <nav class="project-deck" aria-labelledby="projects-heading">
        @foreach($projects as $project)
            @include('components.project-card', ['project' => $project])
        @endforeach
    </nav>

    <footer class="project-home__footer">
        <p>Made for the love of piano.</p>
        <nav aria-label="Footer">
            <a href="{{ route('contact') }}">Contact</a>
            <a href="{{ route('privacy') }}">Privacy</a>
            <a href="{{ route('terms') }}">Terms</a>
        </nav>
        <small>© {{ date('Y') }} PianoLIT</small>
    </footer>
</div>
@endsection
