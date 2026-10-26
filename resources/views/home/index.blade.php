@extends('layouts.app', ['projectHome' => true, 'bodyClass' => 'project-home', 'title' => 'PianoLIT — A world of piano.'])

@section('content')
<div class="project-home__page">
    @include('components.layout.public.header')

    <section class="project-home__hero" aria-labelledby="home-heading">
        <h1 id="home-heading">Welcome to <span>PianoLIT</span></h1>
        <p>A world of music, learning, and inspiration for everyone who loves piano.</p>
    </section>

    @include('home.components.project-deck', ['projects' => $projects])

    <div class="project-home__room">
        <div class="project-home__instrument" aria-hidden="true">
            <img class="project-home__piano" src="{{ asset(mix('images/home/editorial/piano.webp')) }}" width="1402" height="1122" alt="" draggable="false">
        </div>
    </div>

    @include('components.layout.public.footer')
</div>
@endsection
