@extends('layouts.app', ['title' => 'PianoLIT '.$projectTitle.' | Coming up soon', 'bodyClass' => 'project-coming-soon'])

@section('content')
@php($artwork = 'images/home/projects/'.$project.'.svg')
<section class="project-coming-soon__content" aria-label="{{ $projectTitle }} coming soon">
    <img class="project-coming-soon__art" src="{{ asset(mix($artwork)) }}"
         width="320" height="280" alt="{{ $projectTitle }}" fetchpriority="high">
    <h1>Coming up soon!</h1>
    <a href="{{ route('home') }}">@icon('arrow-left')Back to PianoLIT</a>
</section>
@endsection
