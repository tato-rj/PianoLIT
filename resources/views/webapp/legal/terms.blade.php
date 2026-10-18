@extends('webapp.layouts.app')

@section('content')
@include('webapp.layouts.header')

<section>
	@include('components.legal.terms')
</section>
<section class="mt-4" id="audio-credits">
    <h3>Audio credits</h3>
    <p>Female metronome counting adapts “Counting 1 to 10” by <a href="https://freesound.org/people/TyLean/sounds/390514/" target="_blank" rel="noopener noreferrer">TyLean Tuijl</a>, licensed under <a href="https://creativecommons.org/licenses/by/3.0/" target="_blank" rel="noopener noreferrer">CC BY 3.0</a>. The recording was trimmed, adjusted for timing, normalized and converted for playback.</p>
</section>
@endsection
