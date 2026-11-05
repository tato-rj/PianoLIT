@extends('webapp.layouts.app', ['title' => 'Find your match'])

@section('content')
@component('webapp.layouts.header', ['title' => 'Find your match', 'subtitle' => 'Answer questions about your level and musical preferences.'])
    @include('webapp.tour.button')
@endcomponent
@include('webapp.tour.modal', ['autoOpen' => true])
<noscript><p>Please enable JavaScript to find your match.</p></noscript>
@endsection
