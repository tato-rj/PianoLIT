@extends('layouts.app', ['projectHome' => true, 'bodyClass' => 'project-home', 'title' => 'PianoLIT — Piano music and learning'])

@section('content')
<div class="project-home__page">
    @include('components.layout.public.header', ['showBrandName' => true])

    <div class="container project-home__content">
        <section class="project-home__hero row align-items-end gy-4 mx-lg-4" aria-labelledby="home-heading">
            <div class="col-12 col-lg-6 col-xxl-5">
                <p class="project-home__eyebrow">Piano pieces, tools, and resources</p>
                <h1 id="home-heading" class="text-nowrap">Piano music<br>and learning</h1>
            </div>
            <div class="col-12 col-lg-6 col-xxl-7">
                <p class="project-home__intro">Find pieces to play.<br>Study music theory.<br>Practice with tools and games.</p>
                <p class="project-home__invitation mb-0">Choose a section @icon('arrow-down', ['mr' => 0, 'ml' => 1])</p>
            </div>
        </section>

        @include('home.components.project-deck', ['projects' => $projects])
    </div>

    @include('components.layout.public.footer', ['homeWorlds' => true])
    @include('home.components.scroll-hint')
</div>
@endsection
