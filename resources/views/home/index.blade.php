@extends('layouts.app', ['projectHome' => true, 'bodyClass' => 'project-home', 'title' => 'PianoLIT — Many worlds, one love of music'])

@section('content')
<div class="project-home__page">
    @include('components.layout.public.header', ['showBrandName' => true])

    <div class="container project-home__content">
        <section class="project-home__hero row align-items-end gy-4 mx-lg-4" aria-labelledby="home-heading">
            <div class="col-12 col-lg-6 col-xxl-5">
                <p class="project-home__eyebrow">A home for your musical curiosity</p>
                <h1 id="home-heading" class="text-nowrap">Many worlds.<br>One love of music.</h1>
            </div>
            <div class="col-12 col-lg-6 col-xxl-7">
                <p class="project-home__intro">Find your next piece.<br>Follow an idea. Play a little.<br>Every path starts at PianoLIT.</p>
                <p class="project-home__invitation mb-0">Choose a world to begin @icon('arrow-down', ['mr' => 0, 'ml' => 1])</p>
            </div>
        </section>

        @include('home.components.project-deck', ['projects' => $projects])
    </div>

    @include('components.layout.public.footer', ['homeWorlds' => true])
</div>
@endsection
