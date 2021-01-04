@extends('layouts.app', [
    'title' => 'The Ultimate Chord Finder | ' . config('app.name'),
    'popup' => 'ebook',
    'popupAlways' => true,
    'shareable' => [
        'keywords' => 'chords,chord finder,music theory,harmony',
        'title' => 'The Ultimate Chord Finder',
        'description' => 'Give us the notes and we\'ll tell you the chords you can make with them. Also learn how this process works with an easy step-by-step guide.',
        'thumbnail' => asset('images/misc/thumbnails/chords.jpg'),
        'created_at' => carbon('10-08-2019'),
        'updated_at' => carbon('20-08-2019')
    ]])

@section('content')

    @pagetitle([
        'version' => '2.0',
        'title' => 'Chord Finder', 
        'subtitle' => 'Just tell us the notes and we\'ll show the most likely chords you can make with them'])

    @empty($request)
        @include('tools.chord-finder.empty')
    @else
        @include('tools.chord-finder.results.index')
    @endempty

@include('tools.chord-finder.error')

{{-- @popup(['view' => 'crashcourse']) --}}
@endsection

@push('scripts')
@addthis
<script type="text/javascript" src="{{mix('js/tone.js')}}"></script>
<script type="text/javascript" src="{{asset('js/components/piano.js')}}"></script>
<script type="text/javascript" src="{{asset('js/components/chord-finder.js')}}"></script>
@endpush