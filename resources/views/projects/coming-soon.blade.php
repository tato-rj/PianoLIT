@extends('layouts.app', ['title' => 'PianoLIT '.$projectTitle.' | Coming up soon', 'bodyClass' => 'project-coming-soon'])

@section('content')
<section class="project-coming-soon__content" aria-label="{{ $projectTitle }} coming soon">
    <h1>Coming up soon!</h1>
    <a href="{{ route('home') }}">@icon('arrow-left')Back to PianoLIT</a>
</section>
@endsection
